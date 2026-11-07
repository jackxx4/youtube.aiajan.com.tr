<?php

namespace App\Services;

use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class YouTubeChannelService
{
    /**
     * Google OAuth erişim belirteciyle kullanıcının yetkili YouTube kanallarını doğrudan Google REST API'den çeker.
     * Harici kütüphane bağımlılığı olmadan yerel cURL ile çalışır.
     */
    public function listMine(string $accessToken): Collection
    {
        if (empty($accessToken)) {
            Log::warning('YouTubeChannelService::listMine boş accessToken aldı.');
            return collect();
        }

        $url = 'https://www.googleapis.com/youtube/v3/channels?part=snippet,contentDetails,statistics&mine=true&maxResults=50';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        if (!$response || $httpCode !== 200) {
            Log::warning('YouTube API channels.list hatası', [
                'http_code' => $httpCode,
                'curl_error' => $curlErr,
                'response' => mb_substr((string) $response, 0, 500),
            ]);
            return collect();
        }

        $data = json_decode($response, true);
        $items = $data['items'] ?? [];

        Log::info('YouTube API channels.list başarılı', ['channel_count' => count($items)]);

        return collect($items)->map(function ($item) {
            $snippet = $item['snippet'] ?? [];
            $stats = $item['statistics'] ?? [];
            return [
                'youtube_channel_id' => $item['id'] ?? '',
                'title' => $snippet['title'] ?? 'YouTube Kanalı',
                'thumbnail_url' => $snippet['thumbnails']['default']['url'] ?? ($snippet['thumbnails']['high']['url'] ?? null),
                'custom_url' => $snippet['customUrl'] ?? null,
                'subscriber_count' => isset($stats['subscriberCount']) ? (int) $stats['subscriberCount'] : null,
                'view_count' => isset($stats['viewCount']) ? (int) $stats['viewCount'] : null,
            ];
        })->filter(fn ($c) => !empty($c['youtube_channel_id']));
    }

    /**
     * Google Marka Hesabı (@pages.plusgoogle.com) veya profil adı üzerinden
     * en uygun YouTube kanalını (ID, başlık, handle, avatar) bulur.
     */
    public function resolveBestChannelByName(string $name, ?string $email = null, ?string $avatar = null): ?array
    {
        $candidates = [];
        $cleanName = trim($name);
        if ($cleanName !== '') {
            $candidates[] = $cleanName;
        }

        if ($email && str_contains($email, '@pages.plusgoogle.com')) {
            $prefix = explode('@', $email)[0];
            $brandSlug = preg_replace('/-\d+$/', '', $prefix);
            if ($brandSlug && !in_array($brandSlug, $candidates, true)) {
                $candidates[] = $brandSlug;
            }
        }

        foreach ($candidates as $query) {
            $found = $this->searchPublicChannels($query, 6);
            if ($found->isEmpty()) {
                continue;
            }

            $normQuery = mb_strtolower(preg_replace('/\s+/', '', $query));

            // 1. Tam başlık veya handle eşleşmesi ara
            $exact = $found->first(function ($ch) use ($normQuery, $query) {
                $normTitle = mb_strtolower(preg_replace('/\s+/', '', $ch['title'] ?? ''));
                $normHandle = mb_strtolower(ltrim($ch['custom_url'] ?? '', '@'));
                return $normTitle === $normQuery
                    || mb_strtolower($ch['title'] ?? '') === mb_strtolower($query)
                    || $normHandle === $normQuery
                    || str_starts_with($normHandle, $normQuery);
            });

            if ($exact) {
                if (empty($exact['thumbnail_url']) && $avatar) {
                    $exact['thumbnail_url'] = $avatar;
                }
                return $exact;
            }

            // 2. İlk sonucu döndür
            $first = $found->first();
            if ($first) {
                if (empty($first['thumbnail_url']) && $avatar) {
                    $first['thumbnail_url'] = $avatar;
                }
                return $first;
            }
        }

        return null;
    }

    /**
     * Hassas OAuth izni (youtube.readonly) gerekmeden, açık YouTube dizini üzerinden
     * kanal adı, @handle veya hesap ismiyle eşleşen YouTube kanallarını bulur.
     */
    public function searchPublicChannels(string $query, int $limit = 6): Collection
    {
        $query = trim($query);
        if (empty($query)) {
            return collect();
        }

        $q = urlencode($query);
        $url = "https://www.youtube.com/results?search_query={$q}&sp=EgIQAg%3D%3D";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$html || $httpCode !== 200) {
            return collect();
        }

        $results = [];
        if (preg_match('/var ytInitialData = (\{.*?\});<\/script>/s', $html, $m)) {
            $data = json_decode($m[1], true);
            $sections = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
            foreach ($sections as $sec) {
                $contents = $sec['itemSectionRenderer']['contents'] ?? [];
                foreach ($contents as $item) {
                    if (!isset($item['channelRenderer'])) {
                        continue;
                    }
                    $cr = $item['channelRenderer'];
                    $id = $cr['channelId'] ?? '';
                    $title = $cr['title']['simpleText'] ?? '';

                    if (empty($id) || empty($title) || str_ends_with($title, ' - Topic')) {
                        continue;
                    }

                    $handle = $cr['subscriberCountText']['simpleText'] ?? null;
                    if ($handle && !str_starts_with($handle, '@')) {
                        $canon = $cr['navigationEndpoint']['browseEndpoint']['canonicalBaseUrl'] ?? null;
                        $handle = ($canon && str_starts_with($canon, '/@')) ? substr($canon, 1) : null;
                    }

                    $thumbs = $cr['thumbnail']['thumbnails'] ?? [];
                    $lastThumb = !empty($thumbs) ? end($thumbs)['url'] : null;
                    if ($lastThumb && str_starts_with($lastThumb, '//')) {
                        $lastThumb = 'https:' . $lastThumb;
                    }

                    $results[$id] = [
                        'youtube_channel_id' => $id,
                        'title'              => $title,
                        'custom_url'         => $handle,
                        'thumbnail_url'      => $lastThumb,
                    ];

                    if (count($results) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        return collect(array_values($results));
    }

    /**
     * Kanal sisteme eklendiğinde son 10 videosunu otomatik çeker ve veritabanına kaydeder.
     * 1. Öncelik: Resmi YouTube RSS beslemesi (Hızlı, temiz, sıfır kota)
     * 2. Yedek: YouTube kanal web sayfası + OEmbed
     */
    public function syncChannelVideos(YouTubeChannel $channel): int
    {
        $existingCount = Video::where('youtube_channel_id', $channel->id)->count();
        if ($existingCount >= 10) {
            return $existingCount;
        }

        $savedCount = 0;

        // 1. Yöntem: Resmi YouTube XML RSS Beslemesi
        if (!empty($channel->youtube_channel_id)) {
            $rssUrl = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . urlencode($channel->youtube_channel_id);
            $ch = curl_init($rssUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
            $xmlData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 200 && $xmlData) {
                try {
                    $xml = @simplexml_load_string($xmlData);
                    if ($xml && isset($xml->entry)) {
                        foreach ($xml->entry as $entry) {
                            $ytNamespaces = $entry->getNamespaces(true);
                            $media = $entry->children($ytNamespaces['media'] ?? null);
                            $yt = $entry->children($ytNamespaces['yt'] ?? null);

                            $videoId = (string) ($yt->videoId ?? '');
                            $title = (string) ($entry->title ?? '');
                            $thumb = null;
                            if ($media && isset($media->group->thumbnail)) {
                                $attrs = $media->group->thumbnail->attributes();
                                $thumb = (string) ($attrs['url'] ?? '');
                            }
                            if (!$thumb && $videoId) {
                                $thumb = 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg';
                            }

                            if (!empty($videoId)) {
                                try {
                                    Video::updateOrCreate(
                                        ['youtube_video_id' => $videoId],
                                        [
                                            'youtube_channel_id' => $channel->id,
                                            'title'              => $title ?: ('Video ' . $videoId),
                                            'thumbnail_url'      => $thumb,
                                            'published_at'       => !empty($entry->published) ? (string) $entry->published : now(),
                                            'availability'       => 'available',
                                            'last_checked_at'    => now(),
                                        ]
                                    );
                                    $savedCount++;
                                } catch (\Throwable $e) {
                                    Log::warning("RSS video kaydı atlandı: {$videoId}", ['err' => $e->getMessage()]);
                                }
                            }

                            if ($savedCount >= 10) {
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('YouTube RSS parse hatası: ' . $e->getMessage());
                }
            }
        }

        if ($savedCount >= 3) {
            return $savedCount;
        }

        // 2. Yöntem: Kanal Web Sayfasından Video Listesi (Yedek)
        $targetUrl = $channel->custom_url
            ? 'https://www.youtube.com/' . ltrim($channel->custom_url, '/') . '/videos'
            : 'https://www.youtube.com/channel/' . $channel->youtube_channel_id . '/videos';

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $html = curl_exec($ch);

        if ($html) {
            preg_match_all('/"videoId":"([a-zA-Z0-9_-]{11})"/', $html, $matches);
            if (!empty($matches[1])) {
                $videoIds = array_unique($matches[1]);
                foreach (array_slice($videoIds, 0, 10) as $vid) {
                    $meta = null;
                    try {
                        $oembedUrl = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $vid . '&format=json';
                        $chOe = curl_init($oembedUrl);
                        curl_setopt_array($chOe, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT => 4,
                            CURLOPT_SSL_VERIFYPEER => true,
                        ]);
                        $raw = curl_exec($chOe);
                        $meta = $raw ? json_decode($raw, true) : null;
                    } catch (\Throwable) {}

                    try {
                        Video::updateOrCreate(
                            ['youtube_video_id' => $vid],
                            [
                                'youtube_channel_id' => $channel->id,
                                'title'              => $meta['title'] ?? ('Video ' . $vid),
                                'thumbnail_url'      => $meta['thumbnail_url'] ?? ('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg'),
                                'published_at'       => now(),
                                'availability'       => 'available',
                                'last_checked_at'    => now(),
                            ]
                        );
                        $savedCount++;
                    } catch (\Throwable $e) {
                        Log::warning("HTML video kaydı atlandı: {$vid}", ['err' => $e->getMessage()]);
                    }
                }
            }
        }

        return $savedCount;
    }

    public function assertConfigured(): void
    {
        $id = config('services.google.client_id');
        $secret = config('services.google.client_secret');
        if (!$id || !$secret || $id === 'BURAYA_YAZILACAK' || $secret === 'BURAYA_YAZILACAK') {
            throw new RuntimeException('Google OAuth bilgileri henüz yapılandırılmadı.');
        }
    }
}
