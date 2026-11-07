<?php

namespace App\Http\Controllers;

use App\Models\UserVideoSelection;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VideoShowcaseController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        if (!$channel) {
            return redirect()->route('channels.select')
                ->withErrors(['channel' => 'Video seçimi için önce bir kanal bağlamalısınız.']);
        }

        $videos = Video::where('youtube_channel_id', $channel->id)
            ->where('availability', 'available')
            ->latest('published_at')
            ->get();

        $selectedIds = UserVideoSelection::where('user_id', $user->id)
            ->pluck('video_id')
            ->all();

        return view('videos.showcase', compact('videos', 'selectedIds', 'channel'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'video_ids'   => ['nullable', 'array', 'max:3'],
            'video_ids.*' => ['integer', 'distinct', 'exists:videos,id'],
        ]);

        $ids = $data['video_ids'] ?? [];
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        abort_unless($channel, 422, 'Aktif kanal bulunamadı.');

        if (!empty($ids)) {
            $valid = Video::whereIn('id', $ids)
                ->where('youtube_channel_id', $channel->id)
                ->where('availability', 'available')
                ->count();
            abort_unless($valid === count($ids), 422, 'Yalnızca kendi kanalınızdaki videolar seçilebilir.');
        }

        DB::transaction(function () use ($user, $ids) {
            UserVideoSelection::where('user_id', $user->id)->delete();
            foreach ($ids as $videoId) {
                UserVideoSelection::create(['user_id' => $user->id, 'video_id' => $videoId]);
            }
        });

        return redirect()->route('videos.showcase')
            ->with('status', count($ids) . ' video vitrine eklendi.');
    }
}
