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

        $googleChannelIds = $profile->google_channel_ids ?? [];
        $ownedChannels = $googleChannelIds
            ? YouTubeChannel::whereIn('youtube_channel_id', $googleChannelIds)->orderBy('title')->get()
            : collect();

        return view('profile.show', compact(
            'profile',
            'channel',
            'isMe',
            'activeLink',
            'allLinks',
            'ownedChannels',
            'followsCount',
            'followersCount',
            'isSupporter'
        ));
    }
}
