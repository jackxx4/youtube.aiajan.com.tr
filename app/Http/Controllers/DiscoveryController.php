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
  $shown=DB::table('user_impressions')->where('viewer_user_id',$me->id)->where('pool_type',$pool)->whereDate('shown_at',today())->pluck('shown_user_id');
  $query=User::where('status','active')->where('id','<>',$me->id)->whereNotIn('id',$followed)->whereNotIn('id',$shown)->whereHas('activeChannelLink');
  if($pool==='supporter') $query->whereHas('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  else $query->whereDoesntHave('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  $users=$query->inRandomOrder()->limit(10)->get()->load('activeChannelLink.channel');
  foreach($users as $candidate) DB::table('user_impressions')->insert(['viewer_user_id'=>$me->id,'shown_user_id'=>$candidate->id,'pool_type'=>$pool,'shown_at'=>now()]);
  return view('discovery.users',compact('users','pool'));
 }
 public function follow(Request $request,string $pool): RedirectResponse {
  abort_unless(in_array($pool,['normal','supporter'],true),404);
  $data=$request->validate(['user_ids'=>['required','array','size:3'],'user_ids.*'=>['integer','distinct','exists:users,id']]); $me=$request->user();
  $query=User::whereIn('id',$data['user_ids'])->where('status','active')->whereHas('activeChannelLink');
  if($pool==='supporter') $query->whereHas('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  else $query->whereDoesntHave('supporterStatuses',fn($q)=>$q->where('status','active')->where('ends_at','>',now()));
  abort_unless($query->where('id','<>',$me->id)->count()===3,422,'Seçimleriniz keşif havuzu kurallarına uymuyor.');
  $today=PlatformFollow::where('follower_user_id',$me->id)->whereDate('created_at',today())->count();
  abort_if($today+3>6,429,'Günlük 6 platform takip sınırına ulaştınız.');
  DB::transaction(function() use($data,$me){ foreach($data['user_ids'] as $id) PlatformFollow::firstOrCreate(['follower_user_id'=>$me->id,'followed_user_id'=>$id],['created_at'=>now()]); });
  return redirect()->route('discovery.users',$pool)->with('status','Platform takipleriniz kaydedildi.');
 }
 public function videos(): View {
  $videos=Video::with('channel')->where('availability','available')->latest('published_at')->limit(10)->get();
  return view('discovery.videos',compact('videos'));
 }
}