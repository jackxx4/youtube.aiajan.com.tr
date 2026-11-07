<?php

namespace App\Services;

use Illuminate\Support\Collection;
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
            return collect();
        }

        $url = 'https://www.googleapis.com/youtube/v3/channels?part=snippet,statistics&mine=true&maxResults=50';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$response || $httpCode !== 200) {
            return collect();
        }

        $data = json_decode($response, true);
        $items = $data['items'] ?? [];

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

    public function assertConfigured(): void
    {
        $id = config('services.google.client_id');
        $secret = config('services.google.client_secret');
        if (!$id || !$secret || $id === 'BURAYA_YAZILACAK' || $secret === 'BURAYA_YAZILACAK') {
            throw new RuntimeException('Google OAuth bilgileri henüz yapılandırılmadı.');
        }
    }
}
