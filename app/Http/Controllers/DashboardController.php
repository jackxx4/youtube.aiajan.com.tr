<?php

namespace App\Http\Controllers;

use App\Models\PlatformFollow;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $activeLink = $user->activeChannelLink()->with('channel')->first();
        $channel = $activeLink?->channel;
        $isSupporter = $user->isSupporter();

        // 1. Dört Temel Topluluk Metriği (Platform Takibi, Topluluk İlgisi, vb.)
        $followsCount = $user->follows()->count();
        $followersCount = $user->followers()->count();
        $followsToday = PlatformFollow::where('follower_user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        // 2. Vitrindeki Videolar (En fazla 3 adet seçili video)
        $selectedVideoIds = UserVideoSelection::where('user_id', $user->id)
            ->pluck('video_id')
            ->all();

        $showcaseVideos = Video::whereIn('id', $selectedVideoIds)
            ->where('availability', 'available')
            ->get();

        // 3. Kullanıcının Diğer Kayıtlı Kanalları ve Bağlantı Geçmişi (Tekrarsız)
        $allLinks = $user->channelLinks()->with('channel')->orderByDesc('started_at')->get();
        $storedIds = (array) ($user->google_channel_ids ?? []);
        $linkedIds = $allLinks->pluck('channel.youtube_channel_id')->filter()->all();
        $allUserChannelIds = array_values(array_unique(array_filter(array_merge($storedIds, $linkedIds))));

        $ownedChannels = !empty($allUserChannelIds)
            ? YouTubeChannel::whereIn('youtube_channel_id', $allUserChannelIds)->orderBy('title')->get()
            : collect();

        // Aktif kanal haricindeki diğer alternatif kanallar
        $otherChannels = $ownedChannels->reject(fn($c) => $channel && $c->id === $channel->id)->values();

        // Geçmiş kanal bağlantıları
        $pastLinks = $allLinks->whereNotNull('ended_at')->values();

        $userChannels = $ownedChannels;

        return view('dashboard', compact(
            'user',
            'channel',
            'activeLink',
            'isSupporter',
            'followsCount',
            'followersCount',
            'followsToday',
            'selectedVideoIds',
            'showcaseVideos',
            'userChannels',
            'otherChannels',
            'pastLinks'
        ));
    }
}
