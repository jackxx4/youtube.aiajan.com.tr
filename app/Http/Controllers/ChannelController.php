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
        $active = $request->user()->activeChannelLink()->with('channel')->first();
        if ($active && $active->started_at && $active->started_at->gt(now()->subDays(30))) {
            return redirect()->route('dashboard')->with('status', 'Zaten aktif bir YouTube kanalınız bağlı.');
        }

        $ids = session('available_youtube_channels', []);

        // Session'da liste yoksa kullanıcının kayıtlı OAuth token'ı varsa kanalları çekmeyi dene
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

        $channels = !empty($ids)
            ? YouTubeChannel::whereIn('youtube_channel_id', $ids)->orderBy('title')->get()
            : collect();

        return view('channels.select', compact('channels', 'active'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $active = $user->activeChannelLink()->first();

        if ($active && $active->started_at && $active->started_at->gt(now()->subDays(30))) {
            return back()->withErrors(['channel_input' => 'Kanalınızı 30 günlük süre dolmadan değiştiremezsiniz.']);
        }

        // 1. Yöntem: Kanal bağlantısı veya kullanıcı adı (@kullaniciadi) girilmişse
        if ($request->filled('channel_input')) {
            $input = trim((string) $request->input('channel_input'));
            $resolved = $this->resolveYouTubeChannel($input);

            if (!$resolved || empty($resolved['channel_id'])) {
                return back()->withErrors([
                    'channel_input' => 'YouTube kanalı tespit edilemedi. Lütfen geçerli bir kanal kullanıcı adı (@kanaladi) veya kanal linki girin.'
                ])->withInput();
            }

            try {
                DB::transaction(function () use ($user, $active, $resolved) {
                    $channel = YouTubeChannel::updateOrCreate(
                        ['youtube_channel_id' => $resolved['channel_id']],
                        [
                            'title' => $resolved['title'] ?: ($user->name . ' YouTube Kanalı'),
                            'thumbnail_url' => $resolved['thumbnail_url'] ?: $user->avatar_url,
                            'custom_url' => $resolved['custom_url'] ?? null,
                            'last_synced_at' => now(),
                        ]
                    );

                    if (UserChannelLink::where('youtube_channel_id', $channel->id)->whereNull('ended_at')->where('user_id', '<>', $user->id)->exists()) {
                        throw new \DomainException('Bu YouTube kanalı halihazırda başka bir platform üyesine bağlı.');
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
                return back()->withErrors(['channel_input' => $e->getMessage()])->withInput();
            } catch (\Throwable $e) {
                report($e);
                return back()->withErrors(['channel_input' => 'Kanal kaydedilirken hata oluştu: ' . $e->getMessage()])->withInput();
            }

            session()->forget('available_youtube_channels');
            return redirect()->route('dashboard')->with('status', 'YouTube kanalınız başarıyla bağlandı! Topluluğa hoş geldiniz.');
        }

        // 2. Yöntem: Listeden kanal seçilmişse
        if ($request->filled('channel_id')) {
            $data = $request->validate([
                'channel_id' => ['required', 'string', 'exists:youtube_channels,youtube_channel_id']
            ]);

            abort_unless(in_array($data['channel_id'], session('available_youtube_channels', []), true), 403);

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
            } catch (\Throwable $e) {
                return back()->withErrors(['channel_id' => 'Kanal bağlantısı kaydedilemedi.']);
            }

            session()->forget('available_youtube_channels');
            return redirect()->route('dashboard')->with('status', 'YouTube kanalınız başarıyla bağlandı.');
        }

        return back()->withErrors(['channel_input' => 'Lütfen bağlamak istediğiniz YouTube kanalının kullanıcı adını (@kanaladi) veya bağlantısını girin.']);
    }

    /**
     * YouTube sayfasından açık meta verilerini çekerek kanal ID, başlık ve avatarı tespit eder.
     */
    private function resolveYouTubeChannel(string $input): ?array
    {
        $input = trim($input);
        if (empty($input)) {
            return null;
        }

        // URL normalizasyonu
        if (str_starts_with($input, '@')) {
            $url = 'https://www.youtube.com/' . $input;
            $customUrl = $input;
        } elseif (preg_match('/^UC[a-zA-Z0-9_-]{22}$/', $input)) {
            $url = 'https://www.youtube.com/channel/' . $input;
            $customUrl = null;
        } elseif (filter_var($input, FILTER_VALIDATE_URL)) {
            $url = $input;
            $customUrl = str_contains($input, '@') ? ('@' . explode('@', $input)[1]) : null;
        } else {
            $clean = ltrim($input, '@');
            $url = 'https://www.youtube.com/@' . $clean;
            $customUrl = '@' . $clean;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 7,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$html || $httpCode >= 400) {
            return null;
        }

        // Başlık
        preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $mTitle);
        $title = $mTitle[1] ?? null;

        // Avatar
        preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $mImage);
        $thumbnailUrl = $mImage[1] ?? null;

        // Kanal ID
        preg_match('/channel_id=([a-zA-Z0-9_-]+)/', $html, $mChan);
        $channelId = $mChan[1] ?? null;

        if (!$channelId) {
            preg_match('/<link rel="canonical" href="https:\/\/www\.youtube\.com\/channel\/([a-zA-Z0-9_-]+)"/', $html, $mCanon);
            $channelId = $mCanon[1] ?? null;
        }

        if (!$channelId) {
            preg_match('/"channelId":"([a-zA-Z0-9_-]+)"/', $html, $mJson);
            $channelId = $mJson[1] ?? null;
        }

        if (!$channelId && preg_match('/^UC[a-zA-Z0-9_-]{22}$/', $input)) {
            $channelId = $input;
        }

        if (!$channelId) {
            return null;
        }

        return [
            'channel_id' => $channelId,
            'title' => html_entity_decode($title ?: 'YouTube Kanalı', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'thumbnail_url' => $thumbnailUrl,
            'custom_url' => $customUrl,
        ];
    }
}
