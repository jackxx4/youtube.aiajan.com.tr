<?php
namespace App\Console\Commands;
use App\Models\UserChannelLink; use App\Models\Video;
use App\Models\YouTubeMetricMonthly;
use Google\Client as GoogleClient; use Google\Service\YouTube;
use Illuminate\Console\Command; use Illuminate\Support\Facades\Crypt; use Throwable;
class SyncYouTubeChannels extends Command {
 protected $signature='youtube:sync-channels'; protected $description='Bağlı kanalların son üç videosunu ve kanal istatistiklerini yeniler.';
 public function handle(): int {
  $links=UserChannelLink::with(['user.oauthToken','channel'])->whereNull('ended_at')->get();
  foreach($links as $link) {
   try {
    $token=$link->user->oauthToken;if(!$token)continue;
    $client=new GoogleClient();$client->setApplicationName(config('app.name'));
    $expires=$token->expires_at ? max(0,(int)now()->diffInSeconds($token->expires_at,false)) : 0;
    $client->setAccessToken(['access_token'=>Crypt::decryptString($token->access_token_enc),'created'=>now()->timestamp,'expires_in'=>$expires]);
    if($client->isAccessTokenExpired() && $token->refresh_token_enc) {
     $fresh=$client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($token->refresh_token_enc));
     if(isset($fresh['access_token'])) {$token->access_token_enc=Crypt::encryptString($fresh['access_token']);if(!empty($fresh['refresh_token']))$token->refresh_token_enc=Crypt::encryptString($fresh['refresh_token']);$token->expires_at=now()->addSeconds((int)($fresh['expires_in']??3600));$token->save();}
    }
    if($client->isAccessTokenExpired()) {$this->warn('Token yenilenemedi: '.$link->channel->youtube_channel_id);continue;}
    $yt=new YouTube($client);
    $res=$yt->channels->listChannels('snippet,statistics,contentDetails',['id'=>$link->channel->youtube_channel_id,'maxResults'=>1]);
    $item=$res->getItems()[0]??null;if(!$item)continue;
    $stats=$item->getStatistics();$details=$item->getContentDetails();
    $link->channel->update(['subscriber_count'=>$stats?->getSubscriberCount(),'view_count'=>$stats?->getViewCount(),'last_synced_at'=>now()]);
    YouTubeMetricMonthly::updateOrCreate(['youtube_channel_id'=>$link->channel->id,'metric_month'=>now()->startOfMonth()->toDateString()],['subscriber_count'=>$stats?->getSubscriberCount(),'view_count'=>$stats?->getViewCount(),'collected_at'=>now()]);
    $uploads=$details?->getRelatedPlaylists()?->getUploads();if(!$uploads)continue;
    $playlist=$yt->playlistItems->listPlaylistItems('snippet,contentDetails',['playlistId'=>$uploads,'maxResults'=>3]);
    foreach($playlist->getItems() as $entry) {
     $snippet=$entry->getSnippet();$video=$entry->getContentDetails();$id=$video?->getVideoId();if(!$id)continue;
     Video::updateOrCreate(['youtube_video_id'=>$id],['youtube_channel_id'=>$link->channel->id,'title'=>$snippet?->getTitle()??'YouTube Videosu','thumbnail_url'=>$snippet?->getThumbnails()?->getMedium()?->getUrl(),'published_at'=>$video?->getVideoPublishedAt(),'availability'=>'available','last_checked_at'=>now()]);
    }
    $this->line('Senkronize edildi: '.$link->channel->title);
   } catch(Throwable $e) {report($e);$this->warn('Kanal senkronizasyonu başarısız: '.$link->channel?->youtube_channel_id);}
  }
  return self::SUCCESS;
 }
}