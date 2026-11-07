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
  foreach($users as $candidate) DB::table('user_impressions')->insert(['viewer_user_id'=>$me->id,'shown_user_id'=>$candidate->id,'pool_type'=>$pool,'shown_at'=>now()]);
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
 public function videos(Request $request): View {
  $user=$request->user();
  $selected=session('selected_video_ids');
  try {
   if(is_array($selected) && !empty($selected)) {
    $videos=Video::with('channel')
     ->whereIn('id',$selected)
     ->where('availability','available')
     ->get();
   } else {
    $videos=Video::with('channel')
     ->where('availability','available')
     ->inRandomOrder()
     ->limit(10)
     ->get();
   }
  } catch (\Throwable $e) {
   $videos=collect();
  }
  return view('discovery.videos',compact('videos','selected'));
 }
}