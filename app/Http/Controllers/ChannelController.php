<?php

namespace App\Http\Controllers;

use App\Models\UserChannelLink;
use App\Models\YouTubeChannel;
use App\Services\YouTubeChannelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function index(Request $request, YouTubeChannelService $channelsService): View|RedirectResponse
    {
        $ids = session('available_youtube_channels', []);

        // Session'da liste yoksa kullanıcının kayıtlı token'ı ile kanalları çekmeyi dene
        if (empty($ids)) {
            $token = $request->user()->oauthToken;
            if ($token && $token->access_token_enc) {
                try {
                    $decryptedToken = Crypt::decryptString($token->access_token_enc);
                    $fetched = $channelsService->listMine($decryptedToken);
                    if ($fetched->isNotEmpty()) {
                        $ids = $fetched->pluck('youtube_channel_id')->all();
                        session(['available_youtube_channels' => $ids]);
                        foreach ($fetched as $data) {
                            YouTubeChannel::updateOrCreate(
                                ['youtube_channel_id' => $data['youtube_channel_id']],
                                collect($data)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        if (empty($ids)) {
            $active = $request->user()->activeChannelLink()->with('channel')->first();
            if ($active) {
                return redirect()->route('dashboard')->with('status', 'Zaten bir kanalınız bağlı.');
            }
            return redirect()->route('login', ['auto' => 'google'])->withErrors(['google' => 'Kanal listesi için yeniden Google ile giriş yapın.']);
        }

        $channels = YouTubeChannel::whereIn('youtube_channel_id', $ids)->orderBy('title')->get();
        $active = $request->user()->activeChannelLink()->with('channel')->first();
        return view('channels.select', compact('channels', 'active'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel_id' => ['required', 'string', 'exists:youtube_channels,youtube_channel_id']
        ]);

        abort_unless(in_array($data['channel_id'], session('available_youtube_channels', []), true), 403);

        $user = $request->user();
        $active = $user->activeChannelLink()->first();

        if ($active && $active->started_at && $active->started_at->gt(now()->subDays(30))) {
            return back()->withErrors(['channel_id' => 'Kanalınızı 30 günlük süre dolmadan değiştiremezsiniz.']);
        }

        $channel = YouTubeChannel::where('youtube_channel_id', $data['channel_id'])->firstOrFail();

        try {
            DB::transaction(function () use ($user, $active, $channel) {
                if (UserChannelLink::where('youtube_channel_id', $channel->id)->whereNull('ended_at')->where('user_id', '<>', $user->id)->exists()) {
                    throw new \DomainException('Bu YouTube kanalı başka bir platform hesabına bağlı.');
                }
                if ($active) {
                    $active->update(['ended_at' => now()]);
                }
                UserChannelLink::create([
                    'user_id' => $user->id,
                    'youtube_channel_id' => $channel->id,
                    'started_at' => now()
                ]);
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['channel_id' => $e->getMessage()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withErrors(['channel_id' => 'Kanal bağlantısı kaydedilemedi.']);
        }

        session()->forget('available_youtube_channels');
        return redirect()->route('dashboard')->with('status', 'YouTube kanalınız başarıyla bağlandı.');
    }
}
