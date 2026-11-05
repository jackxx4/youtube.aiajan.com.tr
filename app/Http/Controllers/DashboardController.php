<?php
namespace App\Http\Controllers;
use App\Models\PlatformFollow;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\View\View;
class DashboardController extends Controller {
    public function __invoke(Request $request): View {
        $user=$request->user(); $channel=$user->activeChannelLink()->with('channel')->first()?->channel;
        $followsToday=PlatformFollow::where('follower_user_id',$user->id)->whereDate('created_at',today())->count();
        $videos=Video::with('channel')->where('availability','available')->latest('published_at')->limit(10)->get();
        return view('dashboard',compact('user','channel','followsToday','videos'));
    }
}