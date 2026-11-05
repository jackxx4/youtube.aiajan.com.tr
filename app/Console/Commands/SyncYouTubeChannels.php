<?php
namespace App\Console\Commands;
use App\Models\UserChannelLink;
use App\Models\YouTubeOAuthToken;
use App\Models\Video;
use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Throwable;
class SyncYouTubeChannels extends Command {
 protected $signature='youtube:sync-channels';
 protected $description='Bağlı YouTube kanallarının son üç videosunu ve kanal istatistiklerini yeniler.';
 public function handle(): int {
  $links=UserChannelLink::with(['user.oauthToken','channel'])->whereNull('ended_at')->get();
  foreach($links as $link) {
   try {
    $token=$link->user->oauthToken; if(!$token) continue;
    $client=new GoogleClient(); $client->setApplicationName(config('app.name'));
    $access=Crypt::decryptString($token->access_token_enc);
    $client->setAccessToken(['access_token'=>$access,'created'=>now()->timestamp,'expires_in'=>max(0,now()->diffInSeconds($token->expires_at,false))]);
    if($client->isAccessTokenExpired() && $token->refresh_token_enc) {
     $fresh=$client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($token->refresh_token_enc));
     if(isset($fresh['access_token'])) {
      $token->access_token_enc=Crypt::encryptString($fresh['access_token']);
      if(!empty($fresh['refresh_token'])) $token->refresh_token_enc=Crypt::encryptString($fresh['refresh_token']);
      $token->expires_at=now()->addSeconds((int)($fresh['expires_in']??3600)); $token->save();
     }
    }
    $yt=new YouTube($client);
    $channelResponse=$yt->channels->listChannels('snippet,statistics,contentDetails',['id'=>$link->channel->youtube_channel_id,'maxResults'=>1]);
    $item=$channelResponse->getItems()[0]??null; if(!$item) continue;
    $stats=$item->getStatistics(); $content=$item->getContentDetails();
    $link->channel->update(['subscriber_count'=>$stats?->getSubscriberCount(),'view_count'=>$stats?->getViewCount(),'last_synced_at'=>now()]);
    $uploads=$content?->getRelatedPlaylists()?->getUploads();
    if(!$uploads) continue;
    $playlist=$yt->playlistItems->listPlaylistItems('snippet,contentDetails',['playlistId'=>$uploads,'maxResults'=>3]);
    foreach($playlist->getItems() as $videoItem) {
     $snippet=$videoItem->getSnippet(); $details=$videoItem->getContentDetails(); $videoId=$details?->getVideoId();
     if(!$videoId) continue;
     Video::updateOrCreate(['youtube_video_id'=>$videoId],[
      'youtube_channel_id'=>$link->channel->id,'title'=>$snippet?->getTitle()??'YouTube Videosu',
      'thumbnail_url'=>$snippet?->getThumbnails()?->getMedium()?->getUrl(),
      'published_at'=>$details?->getVideoPublishedAt(),'availability'=>'available','last_checked_at'=>now()
     ]);
    }
    $this->line('Senkronize edildi: '.$link->channel->title);
   } catch(Throwable $e) { report($e); $this->warn('Kanal senkronizasyonu başarısız: '.$link->channel?->youtube_channel_id); }
  }
  return self::SUCCESS;
 }
}