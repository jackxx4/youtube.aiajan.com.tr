<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\YouTubeChannel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, ?string $username = null): View
    {
        $profile = $username
            ? User::where('username', $username)->where('status', 'active')->firstOrFail()
            : $request->user();

        $isMe = ($request->user() && $request->user()->id === $profile->id);
        $activeLink = $profile->activeChannelLink()->with('channel')->first();
        $channel = $activeLink?->channel;
        $allLinks = $profile->channelLinks()->with('channel')->orderByDesc('started_at')->get();
        $followsCount = $profile->follows()->count();
        $followersCount = $profile->followers()->count();
        $isSupporter = $profile->isSupporter();

        $storedIds = (array) ($profile->google_channel_ids ?? []);
        $linkedIds = $allLinks->pluck('channel.youtube_channel_id')->filter()->all();
        $allUserChannelIds = array_values(array_unique(array_filter(array_merge($storedIds, $linkedIds))));

        $ownedChannels = !empty($allUserChannelIds)
            ? YouTubeChannel::whereIn('youtube_channel_id', $allUserChannelIds)->orderBy('title')->get()
            : collect();

        // Aktif kanal haricindeki diğer kayıtlı kanallar (tekrarları önler)
        $otherChannels = $ownedChannels->reject(fn($c) => $channel && $c->id === $channel->id)->values();

        // Yalnızca sonlandırılmış geçmiş kanal bağları (aktif olanı tekrar listelemez)
        $pastLinks = $allLinks->whereNotNull('ended_at')->values();

        return view('profile.show', compact(
            'profile',
            'channel',
            'isMe',
            'activeLink',
            'allLinks',
            'ownedChannels',
            'otherChannels',
            'pastLinks',
            'followsCount',
            'followersCount',
            'isSupporter'
        ));
    }
}
