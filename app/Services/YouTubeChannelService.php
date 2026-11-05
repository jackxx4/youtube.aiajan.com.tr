<?php
namespace App\Services;
use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Illuminate\Support\Collection;
use RuntimeException;
class YouTubeChannelService {
    public function listMine(string $accessToken): Collection {
        $client = new GoogleClient();
        $client->setApplicationName(config('app.name'));
        $client->setAccessToken($accessToken);
        $youtube = new YouTube($client);
        $response = $youtube->channels->listChannels('snippet,statistics', ['mine'=>true,'maxResults'=>50]);
        return collect($response->getItems())->map(function ($item) {
            $snippet=$item->getSnippet(); $stats=$item->getStatistics();
            return [
                'youtube_channel_id'=>$item->getId(),
                'title'=>$snippet?->getTitle() ?? 'YouTube Kanalı',
                'thumbnail_url'=>$snippet?->getThumbnails()?->getDefault()?->getUrl(),
                'custom_url'=>$snippet?->getCustomUrl(),
                'subscriber_count'=>$stats?->getSubscriberCount(),
                'view_count'=>$stats?->getViewCount(),
            ];
        });
    }
    public function assertConfigured(): void {
        $id=config('services.google.client_id'); $secret=config('services.google.client_secret');
        if (!$id || !$secret || $id==='BURAYA_YAZILACAK' || $secret==='BURAYA_YAZILACAK') {
            throw new RuntimeException('Google OAuth bilgileri henüz yapılandırılmadı.');
        }
    }
}