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

        $followsTodayQuery = PlatformFollow::where('follower_user_id', $user->id)
            ->whereDate('created_at', today());
        if ($channel) {
            $followsTodayQuery->where(function ($q) use ($channel) {
                $q->where('follower_channel_id', $channel->id)
                  ->orWhereNull('follower_channel_id');
            });
        }
        $followsToday = $followsTodayQuery->count();

        // 2. Aktif Kanalın Vitrindeki Videoları (En fazla 3 adet seçili video)
        if ($channel) {
            $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
            $selectedVideoIds = UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($channel, $channelVideoIds) {
                    $q->where('youtube_channel_id', $channel->id)
                      ->orWhereIn('video_id', $channelVideoIds);
                })
                ->pluck('video_id')
                ->all();
        } else {
            $selectedVideoIds = UserVideoSelection::where('user_id', $user->id)
                ->pluck('video_id')
                ->all();
        }

        $showcaseVideos = Video::whereIn('id', $selectedVideoIds)
            ->where('availability', 'available')
            ->get();

        // 3. Kullanıcının Diğer Kayıtlı Kanalları ve Her Kanalın Görev Durumu (Tekrarsız)
        $allLinks = $user->channelLinks()->with('channel')->orderByDesc('started_at')->get();
        $storedIds = (array) ($user->google_channel_ids ?? []);
        $linkedIds = $allLinks->pluck('channel.youtube_channel_id')->filter()->all();
        $allUserChannelIds = array_values(array_unique(array_filter(array_merge($storedIds, $linkedIds))));

        $ownedChannels = !empty($allUserChannelIds)
            ? YouTubeChannel::whereIn('youtube_channel_id', $allUserChannelIds)->orderBy('title')->get()
            : collect();

        foreach ($ownedChannels as $uc) {
            $ucVideoIds = Video::where('youtube_channel_id', $uc->id)->pluck('id');
            $uc->showcase_count = UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($uc, $ucVideoIds) {
                    $q->where('youtube_channel_id', $uc->id)
                      ->orWhereIn('video_id', $ucVideoIds);
                })
                ->count();

            $uc->follows_today = PlatformFollow::where('follower_user_id', $user->id)
                ->where('follower_channel_id', $uc->id)
                ->whereDate('created_at', today())
                ->count();

            $uc->is_active = $channel && $channel->id === $uc->id;
        }

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
