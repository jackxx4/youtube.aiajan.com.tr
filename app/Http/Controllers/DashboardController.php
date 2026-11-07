<?php

namespace App\Http\Controllers;

use App\Models\PlatformFollow;
use App\Models\UserVideoSelection;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $activeLink = $user->activeChannelLink()->with('channel')->first();
        $channel = $activeLink?->channel;
        $canChangeChannel = true;
        $followsToday = PlatformFollow::where('follower_user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        // 1. Kullanıcının Vitrine Seçtiği Video ID'leri
        $selectedVideoIds = UserVideoSelection::where('user_id', $user->id)
            ->pluck('video_id')
            ->all();

        // 2. Kullanıcının Ekli Kanal Videoları (Vitrindekiler önce gelecek şekilde)
        $myVideos = collect();
        if ($channel) {
            $myVideos = Video::where('youtube_channel_id', $channel->id)
                ->where('availability', 'available')
                ->latest('published_at')
                ->get()
                ->sortByDesc(fn($v) => in_array($v->id, $selectedVideoIds, true) ? 1 : 0)
                ->values();
        }

        // 3. Vitrindeki Videolar
        $showcaseVideos = $myVideos->whereIn('id', $selectedVideoIds)->values();

        // 4. Topluluk Keşif Havuzu Videoları
        $videos = Video::with('channel')
            ->where('availability', 'available')
            ->latest('published_at')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'user',
            'channel',
            'activeLink',
            'canChangeChannel',
            'followsToday',
            'myVideos',
            'showcaseVideos',
            'selectedVideoIds',
            'videos'
        ));
    }
}
