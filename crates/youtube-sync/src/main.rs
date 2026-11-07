use anyhow::{Context, Result};
use chrono::Utc;
use serde::Deserialize;
use sqlx::mysql::MySqlPoolOptions;
use sqlx::{MySql, Pool, Row};
use std::env;
use std::time::Instant;

#[derive(Debug, Deserialize)]
struct ChannelItem {
    id: String,
    statistics: Option<ChannelStats>,
    #[serde(rename = "contentDetails")]
    content_details: Option<ChannelContentDetails>,
}

#[derive(Debug, Deserialize)]
struct ChannelStats {
    #[serde(rename = "subscriberCount")]
    subscriber_count: Option<String>,
    #[serde(rename = "viewCount")]
    view_count: Option<String>,
}

#[derive(Debug, Deserialize)]
struct ChannelContentDetails {
    #[serde(rename = "relatedPlaylists")]
    related_playlists: Option<RelatedPlaylists>,
}

#[derive(Debug, Deserialize)]
struct RelatedPlaylists {
    uploads: Option<String>,
}

#[derive(Debug, Deserialize)]
struct ChannelResponse {
    items: Option<Vec<ChannelItem>>,
}

#[derive(Debug, Deserialize)]
struct PlaylistItem {
    snippet: Option<PlaylistItemSnippet>,
    #[serde(rename = "contentDetails")]
    content_details: Option<PlaylistItemContentDetails>,
}

#[derive(Debug, Deserialize)]
struct PlaylistItemSnippet {
    title: Option<String>,
    thumbnails: Option<ThumbnailMap>,
}

#[derive(Debug, Deserialize)]
struct ThumbnailMap {
    medium: Option<ThumbnailDetail>,
}

#[derive(Debug, Deserialize)]
struct ThumbnailDetail {
    url: Option<String>,
}

#[derive(Debug, Deserialize)]
struct PlaylistItemContentDetails {
    #[serde(rename = "videoId")]
    video_id: Option<String>,
    #[serde(rename = "videoPublishedAt")]
    video_published_at: Option<String>,
}

#[derive(Debug, Deserialize)]
struct PlaylistResponse {
    items: Option<Vec<PlaylistItem>>,
}

#[tokio::main]
async fn main() -> Result<()> {
    let start_time = Instant::now();
    println!("🚀 [Rust YouTube Sync Engine] Başlatılıyor...");

    // 1. .env dosyasını oku
    let _ = dotenvy::dotenv();

    let db_host = env::var("DB_HOST").unwrap_or_else(|_| "127.0.0.1".to_string());
    let db_port = env::var("DB_PORT").unwrap_or_else(|_| "3306".to_string());
    let db_user = env::var("DB_USERNAME").context("DB_USERNAME .env içinde bulunamadı")?;
    let db_pass = env::var("DB_PASSWORD").unwrap_or_default();
    let db_name = env::var("DB_DATABASE").context("DB_DATABASE .env içinde bulunamadı")?;
    let api_key = env::var("YOUTUBE_API_KEY").context("YOUTUBE_API_KEY .env içinde bulunamadı")?;

    let database_url = format!(
        "mysql://{}:{}@{}:{}/{}",
        db_user, db_pass, db_host, db_port, db_name
    );

    // 2. Veritabanı bağlantı havuzu oluştur
    let pool: Pool<MySql> = MySqlPoolOptions::new()
        .max_connections(5)
        .connect(&database_url)
        .await
        .context("MariaDB/MySQL bağlantısı kurulamadı")?;

    println!("✅ Veritabanına bağlanıldı ({}:{})", db_host, db_port);

    // 3. Aktif bağlı kanalları çek
    let rows = sqlx::query(
        "SELECT uc.id, uc.youtube_channel_id, uc.title \
         FROM user_channel_links ucl \
         JOIN youtube_channels uc ON uc.id = ucl.youtube_channel_id \
         WHERE ucl.ended_at IS NULL"
    )
    .fetch_all(&pool)
    .await
    .context("Aktif kanallar veritabanından çekilemedi")?;

    if rows.is_empty() {
        println!("ℹ️ Senkronize edilecek aktif bağlı kanal bulunamadı.");
        return Ok(());
    }

    println!("📡 {} adet aktif kanal bulundu. Senkronizasyon başlıyor...", rows.len());

    let http_client = reqwest::Client::builder()
        .timeout(std::time::Duration::from_secs(15))
        .build()?;

    let mut synced_count = 0;

    for row in rows {
        let internal_id: u64 = row.get("id");
        let yt_channel_id: String = row.get("youtube_channel_id");
        let title: String = row.get("title");

        match sync_single_channel(&http_client, &pool, internal_id, &yt_channel_id, &api_key).await {
            Ok(v_count) => {
                println!("  ✓ [{}] Senkronize edildi ({} video güncellendi)", title, v_count);
                synced_count += 1;
            }
            Err(e) => {
                eprintln!("  ✗ [{}] Hata oluştu: {:?}", title, e);
            }
        }
    }

    let elapsed = start_time.elapsed();
    println!(
        "🎉 Senkronizasyon tamamlandı: {}/{} kanal güncellendi. Süre: {:.2?}",
        synced_count, synced_count, elapsed
    );

    Ok(())
}

async fn sync_single_channel(
    client: &reqwest::Client,
    pool: &Pool<MySql>,
    internal_id: u64,
    yt_channel_id: &str,
    api_key: &str,
) -> Result<usize> {
    // 1. Kanal bilgilerini al
    let chan_url = format!(
        "https://www.googleapis.com/youtube/v3/channels?part=snippet,statistics,contentDetails&id={}&key={}",
        yt_channel_id, api_key
    );

    let res: ChannelResponse = client.get(&chan_url).send().await?.json().await?;
    let item = res.items.and_then(|mut items| items.pop()).context("Kanal YouTube API'de bulunamadı")?;

    let subs = item.statistics.as_ref().and_then(|s| s.subscriber_count.as_ref()).and_then(|s| s.parse::<u64>().ok());
    let views = item.statistics.as_ref().and_then(|s| s.view_count.as_ref()).and_then(|v| v.parse::<u64>().ok());
    let uploads_id = item.content_details.and_then(|c| c.related_playlists).and_then(|r| r.uploads);

    let now_utc = Utc::now().naive_utc();

    // 2. youtube_channels tablosunu güncelle
    sqlx::query(
        "UPDATE youtube_channels SET subscriber_count = ?, view_count = ?, last_synced_at = ?, updated_at = ? WHERE id = ?"
    )
    .bind(subs)
    .bind(views)
    .bind(now_utc)
    .bind(now_utc)
    .bind(internal_id)
    .execute(pool)
    .await?;

    // 3. youtube_metrics_monthly güncelle/oluştur
    let first_of_month = Utc::now().date_naive().with_day0(0).unwrap_or_else(|| Utc::now().date_naive());
    sqlx::query(
        "INSERT INTO youtube_metrics_monthly (youtube_channel_id, metric_month, subscriber_count, view_count, collected_at) \
         VALUES (?, ?, ?, ?, ?) \
         ON DUPLICATE KEY UPDATE subscriber_count = VALUES(subscriber_count), view_count = VALUES(view_count), collected_at = VALUES(collected_at)"
    )
    .bind(internal_id)
    .bind(first_of_month)
    .bind(subs)
    .bind(views)
    .bind(now_utc)
    .execute(pool)
    .await?;

    // 4. Son 3 videoyu çek
    let mut synced_video_count = 0;
    if let Some(playlist_id) = uploads_id {
        let play_url = format!(
            "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,contentDetails&playlistId={}&maxResults=3&key={}",
            playlist_id, api_key
        );

        if let Ok(play_res) = client.get(&play_url).send().await {
            if let Ok(playlist_data) = play_res.json::<PlaylistResponse>().await {
                if let Some(items) = playlist_data.items {
                    let mut video_ids: Vec<String> = Vec::new();

                    for v_item in items {
                        let snip = v_item.snippet;
                        let c_details = v_item.content_details;
                        let v_id = c_details.as_ref().and_then(|c| c.video_id.clone());

                        if let Some(vid) = v_id {
                            video_ids.push(vid.clone());
                            let title = snip.as_ref().and_then(|s| s.title.clone()).unwrap_or_else(|| "YouTube Videosu".to_string());
                            let thumb = snip.as_ref().and_then(|s| s.thumbnails.as_ref()).and_then(|t| t.medium.as_ref()).and_then(|m| m.url.clone());
                            let pub_at = c_details.as_ref().and_then(|c| c.video_published_at.clone());

                            sqlx::query(
                                "INSERT INTO videos (youtube_video_id, youtube_channel_id, title, thumbnail_url, published_at, availability, last_checked_at, created_at, updated_at) \
                                 VALUES (?, ?, ?, ?, ?, 'available', ?, ?, ?) \
                                 ON DUPLICATE KEY UPDATE title = VALUES(title), thumbnail_url = VALUES(thumbnail_url), availability = 'available', last_checked_at = VALUES(last_checked_at), updated_at = VALUES(updated_at)"
                            )
                            .bind(&vid)
                            .bind(internal_id)
                            .bind(&title)
                            .bind(&thumb)
                            .bind(&pub_at)
                            .bind(now_utc)
                            .bind(now_utc)
                            .bind(now_utc)
                            .execute(pool)
                            .await?;

                            synced_video_count += 1;
                        }
                    }

                    // Artık listede olmayan eski videoları 'unavailable' yap
                    if !video_ids.is_empty() {
                        let query = format!(
                            "UPDATE videos SET availability = 'unavailable', last_checked_at = ? WHERE youtube_channel_id = ? AND youtube_video_id NOT IN ({}) AND availability = 'available'",
                            video_ids.iter().map(|_| "?").collect::<Vec<_>>().join(",")
                        );
                        let mut q = sqlx::query(&query).bind(now_utc).bind(internal_id);
                        for vid in video_ids {
                            q = q.bind(vid);
                        }
                        let _ = q.execute(pool).await;
                    }
                }
            }
        }
    }

    Ok(synced_video_count)
}

