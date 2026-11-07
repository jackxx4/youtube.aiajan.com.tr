<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\YouTubeChannel;
use App\Models\YouTubeOAuthToken;
use App\Services\YouTubeChannelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Socialite;
use Illuminate\Support\Str;
use Throwable;
class GoogleAuthController extends Controller {
 public function redirect(YouTubeChannelService $channels): RedirectResponse {
  try {$channels->assertConfigured();} catch(\RuntimeException $e) {return redirect()->route('login')->withErrors(['google'=>$e->getMessage()]);}
  return Socialite::driver('google')->scopes(['openid','email','profile','https://www.googleapis.com/auth/youtube.readonly'])->with(['access_type'=>'offline','prompt'=>'consent select_account'])->redirect();
 }
 public function callback(YouTubeChannelService $channels): RedirectResponse {
  try {
   $google=Socialite::driver('google')->user(); $available=$channels->listMine($google->token);
   if($available->isEmpty()) return redirect()->route('login')->withErrors(['google'=>'Bu Google hesabında erişilebilir bir YouTube kanalı bulunamadı.']);
   $user=DB::transaction(function() use($google,$available) {
    $user=User::firstOrNew(['google_sub'=>$google->getId()]);
    if(!$user->exists) { $user->username=$this->uniqueUsername($google->getNickname() ?: $google->getName() ?: 'creator'); $user->status='active'; }
    $user->name=$google->getName() ?: 'YouTube Kullanıcısı'; $user->email=$google->getEmail(); $user->avatar_url=$google->getAvatar(); $user->save();
    foreach($available as $data) YouTubeChannel::updateOrCreate(['youtube_channel_id'=>$data['youtube_channel_id']],collect($data)->except(['subscriber_count','view_count'])->all()+['last_synced_at'=>now()]);
    $storedToken=YouTubeOAuthToken::firstOrNew(['user_id'=>$user->id]);
    $storedToken->access_token_enc=Crypt::encryptString($google->token);
    if($google->refreshToken) $storedToken->refresh_token_enc=Crypt::encryptString($google->refreshToken);
    $storedToken->expires_at=$google->expiresIn ? now()->addSeconds($google->expiresIn) : null;
    $storedToken->scopes='openid email profile https://www.googleapis.com/auth/youtube.readonly';
    $storedToken->save();
    session(['available_youtube_channels'=>$available->pluck('youtube_channel_id')->all()]); return $user;
   });
   if($user->status!=='active') return redirect()->route('login')->withErrors(['google'=>'Bu platform hesabı şu anda kullanıma açık değil.']);
   Auth::login($user,true); request()->session()->regenerate();
   $active=$user->activeChannelLink()->first();
   return $active && $active->started_at->gt(now()->subDays(30)) ? redirect()->route('dashboard') : redirect()->route('channels.select');
  } catch(Throwable $e) {report($e);return redirect()->route('login')->withErrors(['google'=>'Google girişi tamamlanamadı. OAuth ayarlarını ve YouTube kanal erişimini kontrol edin.']);}
 }
 public function logout(): RedirectResponse {Auth::logout();request()->session()->invalidate();request()->session()->regenerateToken();return redirect()->route('home');}
 private function uniqueUsername(string $name): string {
  $base=substr(Str::slug($name,'_') ?: 'creator',0,38);$candidate=$base;$i=1;
  while(User::where('username',$candidate)->exists()) $candidate=substr($base,0,38).$i++;
  return $candidate;
 }
}