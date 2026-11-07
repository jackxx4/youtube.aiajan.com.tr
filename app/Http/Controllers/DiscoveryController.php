<?php
namespace App\Http\Controllers;
use App\Models\PlatformFollow;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class DiscoveryController extends Controller {
 public function users(Request $request,string $pool='normal'): View {
  abort_unless(in_array($pool,['normal','supporter'],true),404); $me=$request->user();
  $followed=PlatformFollow::where('follower_user_id',$me->id)->pluck('followed_user_id');
  $blockedByMe=DB::table('user_blocks')->where('blocker_user_id',$me->id)->pluck('blocked_user_id');
  $blockedMe=DB::table('user_blocks')->where('blocked_user_id',$me->id)->pluck('blocker_user_id');
  $base=User::where('status','active')->where('id','<>',$me->id)->whereNotIn('id',$followed)->whereNotIn('id',$blockedByMe)->whereNotIn('id',$blockedMe)->whereHas('activeChannelLink');
  if($pool==='supporter') $base->whereHas('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  else $base->whereDoesntHave('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  $seen=DB::table('user_impressions')->where('viewer_user_id',$me->id)->where('pool_type',$pool)->pluck('shown_user_id');
  $users=(clone $base)->whereNotIn('id',$seen)->inRandomOrder()->limit(10)->get();
  if($users->isEmpty()){DB::table('user_impressions')->where('viewer_user_id',$me->id)->where('pool_type',$pool)->delete();$users=(clone $base)->inRandomOrder()->limit(10)->get();}
  $users->load('activeChannelLink.channel');
  $impressionRows=$users->map(fn($u)=>['viewer_user_id'=>$me->id,'shown_user_id'=>$u->id,'pool_type'=>$pool,'shown_at'=>now()])->all();
  if(!empty($impressionRows)) DB::table('user_impressions')->insert($impressionRows);
  return view('discovery.users',compact('users','pool'));
 }
 public function follow(Request $request,string $pool): RedirectResponse {
  abort_unless(in_array($pool,['normal','supporter'],true),404);
  $data=$request->validate(['user_ids'=>['required','array','size:3'],'user_ids.*'=>['integer','distinct','exists:users,id']]); $me=$request->user();
  $blockedByMe=DB::table('user_blocks')->where('blocker_user_id',$me->id)->pluck('blocked_user_id');
  $blockedMe=DB::table('user_blocks')->where('blocked_user_id',$me->id)->pluck('blocker_user_id');
  $query=User::whereIn('id',$data['user_ids'])->where('status','active')->where('id','<>',$me->id)->whereNotIn('id',$blockedByMe)->whereNotIn('id',$blockedMe)->whereHas('activeChannelLink');
  $query->whereNotIn('id',PlatformFollow::where('follower_user_id',$me->id)->select('followed_user_id'));
  if($pool==='supporter') $query->whereHas('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  else $query->whereDoesntHave('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  abort_unless($query->count()===3,422,'Seçimleriniz keşif havuzu kurallarına uymuyor.');
  $poolCount=PlatformFollow::where('follower_user_id',$me->id)->where('pool_type',$pool)->whereDate('created_at',today())->count();
  $total=PlatformFollow::where('follower_user_id',$me->id)->whereDate('created_at',today())->count();
  abort_if($poolCount+3>3 || $total+3>6,429,'Bu keşif havuzundaki günlük takip sınırına ulaştınız.');
  DB::transaction(function() use($data,$me,$pool){foreach($data['user_ids'] as $id) PlatformFollow::firstOrCreate(['follower_user_id'=>$me->id,'followed_user_id'=>$id],['pool_type'=>$pool,'created_at'=>now()]);});
  return redirect()->route('discovery.users',$pool)->with('status','Platform takipleriniz kaydedildi.');
 }

 /**
  * Video Keşfi:
  * Sistem 10 video gösterir -> Kullanıcı 3 video seçer -> Seçilen 3 video sırayla oynatılır.
  */
 public function videos(Request $request): View {
  $selectedIds = session('selected_video_ids');

  // Eğer kullanıcı 3 video seçmişse Sıralı Oynatıcı modunu aç
  if (is_array($selectedIds) && count($selectedIds) === 3) {
   $selectedVideos = Video::with('channel')
    ->whereIn('id', $selectedIds)
    ->where('availability', 'available')
    ->get();

   if ($selectedVideos->isNotEmpty()) {
    $playlist = $selectedVideos->map(fn($v) => [
     'id'      => (string) $v->youtube_video_id,
     'title'   => (string) $v->title,
     'channel' => (string) ($v->channel?->title ?? 'YouTube Kanalı'),
    ])->values()->all();

    return view('discovery.videos', [
     'mode'           => 'player',
     'selectedVideos' => $selectedVideos,
     'playlist'       => $playlist,
     'videos'         => $selectedVideos,
    ]);
   }
  }

  // Henüz seçim yapılmadıysa: Sistem rastgele 10 video sunar
  try {
   $videos = Video::with('channel')
    ->where('availability', 'available')
    ->inRandomOrder()
    ->limit(10)
    ->get();
  } catch (\Throwable $e) {
   $videos = collect();
  }

  return view('discovery.videos', [
   'mode'     => 'selection',
   'videos'   => $videos,
   'playlist' => [],
  ]);
 }

 public function selectVideos(Request $request): RedirectResponse {
  $data = $request->validate([
   'video_ids'   => ['required', 'array', 'size:3'],
   'video_ids.*' => ['integer', 'distinct', 'exists:videos,id'],
  ], [
   'video_ids.required' => 'Lütfen 3 video seçin.',
   'video_ids.size'     => 'Sistemde sırayla izlemek için tam olarak 3 video seçmelisiniz.',
  ]);

  session(['selected_video_ids' => $data['video_ids']]);
  return redirect()->route('discovery.videos');
 }

 public function resetVideos(): RedirectResponse {
  session()->forget('selected_video_ids');
  return redirect()->route('discovery.videos');
 }
}
