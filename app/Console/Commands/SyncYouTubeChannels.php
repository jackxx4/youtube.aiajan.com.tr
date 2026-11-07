<?php

namespace App\Console\Commands;

use App\Models\UserChannelLink;
use App\Models\Video;
use App\Models\YouTubeMetricMonthly;
use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\Process\Process;
use Throwable;

class SyncYouTubeChannels extends Command
{
    protected $signature = 'youtube:sync-channels';
    protected $description = 'Bağlı kanalların son üç videosunu ve kanal istatistiklerini yeniler.';

    public function handle(): int
    {
        // 1. Rust Yüksek Hızlı Motor Kontrolü (Windows .exe ve Linux/macOS ikilisi)
        $rustBin = base_path('bin/youtube-sync');
        $rustExe = base_path('bin/youtube-sync.exe');
        $bin = file_exists($rustBin) ? $rustBin : (file_exists($rustExe) ? $rustExe : null);

        if ($bin && (PHP_OS_FAMILY === 'Windows' || is_executable($bin))) {
            $this->info('🦀 Yüksek performanslı Rust motoru çalıştırılıyor: ' . $bin);
            $process = new Process([$bin], base_path(), null, null, 300);
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            if ($process->isSuccessful()) {
                return self::SUCCESS;
            }

            $this->warn('Rust motoru başarısız oldu, PHP yedeğine geçiliyor...');
        }

        // 2. Yerel PHP Yedekleme Akışı (Fallback)
        $links = UserChannelLink::with(['user.oauthToken', 'channel'])
            ->whereNull('ended_at')
            ->get();

        if ($links->isEmpty()) {
            $this->info('Senkronize edilecek aktif kanal bulunamadı.');
            return self::SUCCESS;
        }

        foreach ($links as $link) {
            try {
                $token = $link->user?->oauthToken;
                if (!$token) {
                    continue;
                }

                $client = new GoogleClient();
                $client->setApplicationName(config('app.name'));
                $client->setClientId((string) config('services.google.client_id'));
                $client->setClientSecret((string) config('services.google.client_secret'));
                $client->setRedirectUri((string) config('services.google.redirect'));

                $expires = $token->expires_at
                    ? max(0, (int) now()->diffInSeconds($token->expires_at, false))
                    : 0;

                $client->setAccessToken([
                    'access_token' => Crypt::decryptString($token->access_token_enc),
                    'created' => now()->timestamp,
                    'expires_in' => $expires,
                ]);

                // Token süresi dolmuşsa refresh token ile yenile
                if ($client->isAccessTokenExpired() && $token->refresh_token_enc) {
                    $fresh = $client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($token->refresh_token_enc));
                    if (isset($fresh['access_token'])) {
                        $client->setAccessToken($fresh);
                        $token->access_token_enc = Crypt::encryptString($fresh['access_token']);
                        if (!empty($fresh['refresh_token'])) {
                            $token->refresh_token_enc = Crypt::encryptString($fresh['refresh_token']);
                        }
                        $token->expires_at = now()->addSeconds((int) ($fresh['expires_in'] ?? 3600));
                        $token->save();
                    }
                }

                if ($client->isAccessTokenExpired()) {
                    $this->warn('Token yenilenemedi: ' . $link->channel?->youtube_channel_id);
                    continue;
                }

                $yt = new YouTube($client);
                $res = $yt->channels->listChannels('snippet,statistics,contentDetails', [
                    'id' => $link->channel->youtube_channel_id,
                    'maxResults' => 1,
                ]);

                $item = $res->getItems()[0] ?? null;
                if (!$item) {
                    continue;
                }

                $stats = $item->getStatistics();
                $details = $item->getContentDetails();

                // Kanal istatistiklerini güncelle
                $link->channel->update([
                    'subscriber_count' => $stats?->getSubscriberCount(),
                    'view_count' => $stats?->getViewCount(),
                    'last_synced_at' => now(),
                ]);

                // Aylık metrik kaydı
                YouTubeMetricMonthly::updateOrCreate(
                    [
                        'youtube_channel_id' => $link->channel->id,
                        'metric_month' => now()->startOfMonth()->toDateString(),
                    ],
                    [
                        'subscriber_count' => $stats?->getSubscriberCount(),
                        'view_count' => $stats?->getViewCount(),
                        'collected_at' => now(),
                    ]
                );

                // Son 3 videoyu senkronize et
                $uploads = $details?->getRelatedPlaylists()?->getUploads();
                if (!$uploads) {
                    continue;
                }

                $playlist = $yt->playlistItems->listPlaylistItems('snippet,contentDetails', [
                    'playlistId' => $uploads,
                    'maxResults' => 10,
                ]);

                foreach ($playlist->getItems() as $entry) {
                    $snippet = $entry->getSnippet();
                    $video = $entry->getContentDetails();
                    $id = $video?->getVideoId();
                    if (!$id) {
                        continue;
                    }

                    $thumbnailUrl = $snippet?->getThumbnails()?->getMedium()?->getUrl()
                        ?? $snippet?->getThumbnails()?->getDefault()?->getUrl();

                    Video::updateOrCreate(
                        ['youtube_video_id' => $id],
                        [
                            'youtube_channel_id' => $link->channel->id,
                            'title' => $snippet?->getTitle() ?? 'YouTube Videosu',
                            'thumbnail_url' => $thumbnailUrl,
                            'published_at' => $video?->getVideoPublishedAt(),
                            'availability' => 'available',
                            'last_checked_at' => now(),
                        ]
                    );
                }


                $this->line('✓ Senkronize edildi: ' . $link->channel->title);
            } catch (Throwable $e) {
                report($e);
                $this->warn('✗ Kanal senkronizasyonu başarısız: ' . ($link->channel?->youtube_channel_id ?? 'Bilinmeyen'));
            }
        }

        return self::SUCCESS;
    }
}
