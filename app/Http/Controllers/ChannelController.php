<?php

namespace App\Http\Controllers;

use App\Models\UserChannelLink;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use App\Services\YouTubeChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function index(Request $request, YouTubeChannelService $channelsService): View|RedirectResponse
    {
        $user = $request->user();
        $active = $user->activeChannelLink()->with('channel')->first();

        // 1. Kullanıcının oturumundaki, profiline kayıtlı ve geçmişte bağladığı tüm kanalları topla
        $sessionIds = (array) session('available_youtube_channels', []);
        $storedIds = (array) ($user->google_channel_ids ?? []);
        $linkedIds = $user->channelLinks()->with('channel')->get()->pluck('channel.youtube_channel_id')->filter()->all();
        $ids = array_values(array_unique(array_filter(array_merge($sessionIds, $storedIds, $linkedIds))));

        // 2. Eğer kayıtlı liste yoksa kullanıcının OAuth token'ı üzerinden kanalları çekmeyi dene
        if (empty($ids)) {
            $token = $user->oauthToken;
            if ($token && $token->access_token_enc) {
                try {
                    $decryptedToken = Crypt::decryptString($token->access_token_enc);
                    $fetched = $channelsService->listMine($decryptedToken);
                    if ($fetched->isNotEmpty()) {
                        $newIds = $fetched->pluck('youtube_channel_id')->all();
                        $ids = array_values(array_unique(array_merge($ids, $newIds)));
                        session(['available_youtube_channels' => $ids]);
                        foreach ($fetched as $data) {
                            $item = (array) $data;
                            YouTubeChannel::updateOrCreate(
                                ['youtube_channel_id' => $item['youtube_channel_id']],
                                collect($item)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                            );
                        }
                        $user->update(['google_channel_ids' => $ids]);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $channels = !empty($ids)
            ? YouTubeChannel::whereIn('youtube_channel_id', $ids)->orderBy('title')->get()
            : collect();

        foreach ($channels as $ch) {
            $chVideoIds = Video::where('youtube_channel_id', $ch->id)->pluck('id');
            $ch->showcase_count = UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($ch, $chVideoIds) {
                    $q->where('youtube_channel_id', $ch->id)
                      ->orWhereIn('video_id', $chVideoIds);
                })
                ->count();
            $ch->is_active = $active && $active->youtube_channel_id === $ch->id;
        }

        return view('channels.select', compact('channels', 'active'));
    }

    /**
     * Google OAuth penceresinden gelen erişim belirteci ile kullanıcının YouTube kanallarını çeker.
     */
    public function syncGoogleChannels(Request $request, YouTubeChannelService $channelsService): JsonResponse
    {
        $accessToken = (string) $request->input('accessToken');
        if (empty($accessToken)) {
            return response()->json(['success' => false, 'message' => 'Yetkilendirme belirteci bulunamadı.'], 422);
        }

        try {
            $fetched = $channelsService->listMine($accessToken);

            if ($fetched->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu Google hesabına bağlı herhangi bir YouTube kanalı bulunamadı veya yetki verilmedi.'
                ]);
            }

            $newIds = [];
            foreach ($fetched as $data) {
                $ch = YouTubeChannel::updateOrCreate(
                    ['youtube_channel_id' => $data['youtube_channel_id']],
                    collect($data)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                );
                $newIds[] = $data['youtube_channel_id'];

                try {
                    $channelsService->syncChannelVideos($ch);
                } catch (\Throwable) {}
            }

            $currentIds = (array) ($request->user()->google_channel_ids ?? []);
            $mergedIds = array_values(array_unique(array_filter(array_merge($currentIds, $newIds))));

            session(['available_youtube_channels' => $mergedIds]);
            $request->user()->update(['google_channel_ids' => $mergedIds]);

            $allChannels = YouTubeChannel::whereIn('youtube_channel_id', $mergedIds)->orderBy('title')->get();

            return response()->json([
                'success' => true,
                'channels' => $allChannels->values(),
                'message' => count($allChannels) . ' YouTube kanalı başarıyla listelendi!'
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Kanallar çekilirken hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request, YouTubeChannelService $channelsService): RedirectResponse
    {
        $user = $request->user();
        $active = $user->activeChannelLink()->first();

        // 1. Yöntem: Kanal bağlantısı veya kullanıcı adı (@kullaniciadi) girilmişse
        if ($request->filled('channel_input')) {
            $input = trim((string) $request->input('channel_input'));
            $resolved = $this->resolveYouTubeChannel($input);

            if (!$resolved || empty($resolved['channel_id'])) {
                return back()->withErrors([
                    'channel_input' => 'YouTube kanalı tespit edilemedi. Lütfen geçerli bir kanal kullanıcı adı (@kanaladi) veya linki girin.'
                ])->withInput();
            }

            try {
                $channel = null;
                DB::transaction(function () use ($user, $active, $resolved, &$channel) {
                    $channel = YouTubeChannel::updateOrCreate(
                        ['youtube_channel_id' => $resolved['channel_id']],
                        [
                            'title' => $resolved['title'] ?: ($user->name . ' YouTube Kanalı'),
                            'thumbnail_url' => $resolved['thumbnail_url'] ?: $user->avatar_url,
                            'custom_url' => $resolved['custom_url'] ?? null,
                            'last_synced_at' => now(),
                        ]
                    );

                    // Başka bir aktif kullanıcıya bağlı mı?
                    if (UserChannelLink::where('youtube_channel_id', $channel->id)->whereNull('ended_at')->where('user_id', '<>', $user->id)->exists()) {
                        throw new \DomainException('Bu YouTube kanalı halihazırda başka bir platform üyesine bağlı.');
                    }

                    // Zaten bu kanala bağlı ise değişiklik gerekmez
                    if ($active && $active->youtube_channel_id === $channel->id) {
                        return;
                    }

                    // Önceki tüm aktif bağları kapat (ancak diğer kanallara ait vitrin seçimlerini SAKLA)
                    UserChannelLink::where('user_id', $user->id)->whereNull('ended_at')->update(['ended_at' => now()]);

                    UserChannelLink::create([
                        'user_id' => $user->id,
                        'youtube_channel_id' => $channel->id,
                        'started_at' => now()
                    ]);
                });

                if ($channel) {
                    try {
                        $channelsService->syncChannelVideos($channel);
                    } catch (\Throwable $syncEx) {
                        \Log::warning('syncChannelVideos atlandı: ' . $syncEx->getMessage());
                    }

                    // Kullanıcının kayıtlı kanal listesine ekle
                    $currentIds = (array) ($user->google_channel_ids ?? []);
                    if (!in_array($channel->youtube_channel_id, $currentIds, true)) {
                        $currentIds[] = $channel->youtube_channel_id;
                        $user->update(['google_channel_ids' => $currentIds]);
                    }
                }
            } catch (\DomainException $e) {
                return back()->withErrors(['channel_input' => $e->getMessage()])->withInput();
            } catch (\Throwable $e) {
                report($e);
                return back()->withErrors(['channel_input' => 'Kanal kaydedilirken hata oluştu: ' . $e->getMessage()])->withInput();
            }

            session()->forget('available_youtube_channels');

            $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
            $showcaseCount = UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($channel, $channelVideoIds) {
                    $q->where('youtube_channel_id', $channel->id)
                      ->orWhereIn('video_id', $channelVideoIds);
                })->count();

            if ($showcaseCount === 3) {
                return redirect()->route('dashboard')->with('status', '“' . $channel->title . '” kanalınız aktif edildi. Bu kanalın 3 videosu zaten havuzda yayında.');
            }

            return redirect()->route('videos.showcase')->with('status', '“' . $channel->title . '” YouTube kanalınız bağlandı! Kanalınızın keşif havuzunda yer alması için lütfen son 10 videodan 3 tanesini seçin.');
        }

        // 2. Yöntem: Listeden veya profilden kanal seçilmişse
        if ($request->filled('channel_id')) {
            $data = $request->validate([
                'channel_id' => ['required', 'string', 'exists:youtube_channels,youtube_channel_id']
            ]);

            $channel = YouTubeChannel::where('youtube_channel_id', $data['channel_id'])->first();
            if (!$channel) {
                return back()->withErrors(['channel_id' => 'Seçilen YouTube kanalı bulunamadı.']);
            }

            try {
                DB::transaction(function () use ($user, $active, $channel) {
                    if (UserChannelLink::where('youtube_channel_id', $channel->id)->whereNull('ended_at')->where('user_id', '<>', $user->id)->exists()) {
                        throw new \DomainException('Bu YouTube kanalı başka bir platform hesabına bağlı.');
                    }

                    if ($active && $active->youtube_channel_id === $channel->id) {
                        return;
                    }

                    // Önceki tüm aktif bağları kapat (ancak diğer kanallara ait vitrin seçimlerini SAKLA)
                    UserChannelLink::where('user_id', $user->id)->whereNull('ended_at')->update(['ended_at' => now()]);

                    UserChannelLink::create([
                        'user_id' => $user->id,
                        'youtube_channel_id' => $channel->id,
                        'started_at' => now()
                    ]);
                });

                // Kullanıcının kayıtlı kanal listesine ekle
                $currentIds = (array) ($user->google_channel_ids ?? []);
                if (!in_array($channel->youtube_channel_id, $currentIds, true)) {
                    $currentIds[] = $channel->youtube_channel_id;
                    $user->update(['google_channel_ids' => $currentIds]);
                }

                try {
                    $channelsService->syncChannelVideos($channel);
                } catch (\Throwable $syncEx) {
                    \Log::warning('syncChannelVideos atlandı: ' . $syncEx->getMessage());
                }
            } catch (\DomainException $e) {
                return back()->withErrors(['channel_id' => $e->getMessage()]);
            } catch (\Throwable $e) {
                report($e);
                return back()->withErrors(['channel_id' => 'Kanal bağlantısı kaydedilemedi: ' . $e->getMessage()]);
            }

            session()->forget('available_youtube_channels');

            $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
            $showcaseCount = UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($channel, $channelVideoIds) {
                    $q->where('youtube_channel_id', $channel->id)
                      ->orWhereIn('video_id', $channelVideoIds);
                })->count();

            if ($showcaseCount === 3) {
                return redirect()->route('dashboard')->with('status', '“' . $channel->title . '” kanalınız aktif edildi. Bu kanalın 3 videosu zaten havuzda yayında.');
            }

            return redirect()->route('videos.showcase')->with('status', '“' . $channel->title . '” kanalınız aktif edildi. Bu kanalınızın keşif havuzuna dahil olması için lütfen son 10 videodan 3 tanesini seçin.');
        }

        return back()->withErrors(['channel_input' => 'Lütfen bağlamak istediğiniz YouTube kanalını seçin veya kullanıcı adını girin.']);
    }

    /**
     * Kanal ilk bağlandığında veya değiştirildiğinde son videoları otomatik çeker.
     */
    private function syncInitialVideos(YouTubeChannel $channel): void
    {
        // Kanala ait zaten video varsa tekrar çekmeye gerek yok
        if (Video::where('youtube_channel_id', $channel->id)->exists()) {
            return;
        }

        $targetUrl = $channel->custom_url
            ? 'https://www.youtube.com/' . ltrim($channel->custom_url, '/') . '/videos'
            : 'https://www.youtube.com/channel/' . $channel->youtube_channel_id . '/videos';

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $html = curl_exec($ch);

        if (!$html) {
            return;
        }

        preg_match_all('/"videoId":"([a-zA-Z0-9_-]{11})"/', $html, $matches);
        if (empty($matches[1])) {
            return;
        }

        $videoIds = array_unique($matches[1]);
        foreach (array_slice($videoIds, 0, 10) as $vid) {
            // OEmbed ile başlık ve thumbnail çek
            $meta = null;
            try {
                $oembedUrl = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $vid . '&format=json';
                $chOe = curl_init($oembedUrl);
                curl_setopt_array($chOe, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 4,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                $raw = curl_exec($chOe);
                $meta = $raw ? json_decode($raw, true) : null;
            } catch (\Throwable) {
                // oembed hatası olursa varsayılan değerlerle devam et
            }

            try {
                Video::updateOrCreate(
                    ['youtube_video_id' => $vid],
                    [
                        'youtube_channel_id' => $channel->id,
                        'title'              => $meta['title'] ?? ('Video ' . $vid),
                        'thumbnail_url'      => $meta['thumbnail_url'] ?? ('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg'),
                        'published_at'       => now(),
                        'availability'       => 'available',
                        'last_checked_at'    => now(),
                    ]
                );
            } catch (\Throwable $vidEx) {
                // Duplicate key veya diğer kısıt durumlarında kanal bağlantısını bozma
                \Log::warning("Video updateOrCreate atlandı: {$vid}", ['error' => $vidEx->getMessage()]);
            }
        }
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
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$html || $httpCode >= 400) {
            return null;
        }

        preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $mTitle);
        $title = $mTitle[1] ?? null;

        preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $mImage);
        $thumbnailUrl = $mImage[1] ?? null;

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
