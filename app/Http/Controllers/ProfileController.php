<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserVideoSelection;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, ?string $username = null): View|RedirectResponse
    {
        // Kullanıcı kendi profilini görüntülüyorsa birleştirilen Genel Bakış'a yönlendirilir
        if ($username === null || ($request->user() && $request->user()->username === $username)) {
            return redirect()->route('dashboard');
        }

        $profile = User::where('username', $username)->where('status', 'active')->firstOrFail();
        $activeLink = $profile->activeChannelLink()->with('channel')->first();
        $channel = $activeLink?->channel;
        $isSupporter = $profile->isSupporter();
        $followsCount = $profile->follows()->count();
        $followersCount = $profile->followers()->count();

        // Herkese açık vitrindeki videolar
        $selectedVideoIds = UserVideoSelection::where('user_id', $profile->id)->pluck('video_id')->all();
        $showcaseVideos = Video::whereIn('id', $selectedVideoIds)->where('availability', 'available')->get();

        return view('profile.show', compact(
            'profile',
            'channel',
            'isSupporter',
            'followsCount',
            'followersCount',
            'showcaseVideos'
        ));
    }
}
