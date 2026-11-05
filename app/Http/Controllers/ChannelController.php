<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\UserChannelLink;
use App\Models\YouTubeChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class ChannelController extends Controller {
    public function index(Request $request): View {
        $ids=session('available_youtube_channels',[]);
        $channels=YouTubeChannel::whereIn('youtube_channel_id',$ids)->orderBy('title')->get();
        $active=$request->user()->activeChannelLink()->with('channel')->first();
        return view('channels.select',compact('channels','active'));
    }
    public function store(Request $request): RedirectResponse {
        $data=$request->validate(['channel_id'=>['required','string','exists:youtube_channels,youtube_channel_id']]);
        abort_unless(in_array($data['channel_id'],session('available_youtube_channels',[]),true),403);
        $user=$request->user(); $active=$user->activeChannelLink()->first();
        if ($active && $active->started_at->gt(now()->subDays(30))) return back()->withErrors(['channel_id'=>'Kanalınızı 30 günlük süre dolmadan değiştiremezsiniz.']);
        $channel=YouTubeChannel::where('youtube_channel_id',$data['channel_id'])->firstOrFail();
        try { DB::transaction(function () use ($user,$active,$channel) {
            if ($active) $active->update(['ended_at'=>now()]);
            UserChannelLink::create(['user_id'=>$user->id,'youtube_channel_id'=>$channel->id,'started_at'=>now()]);
        }); } catch (\Illuminate\Database\QueryException $e) {
            return back()->withErrors(['channel_id'=>'Bu YouTube kanalı başka bir platform hesabına bağlı olabilir.']);
        }
        session()->forget('available_youtube_channels');
        return redirect()->route('dashboard')->with('status','YouTube kanalınız bağlandı.');
    }
}