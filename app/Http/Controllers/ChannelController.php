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

        // Yanlışlıkla manuel olarak eklenmiş @sadeceyorumluyor gibi doğrulanmamış kanalı temizle
        $unverified = YouTubeChannel::where('custom_url', 'like', '%sadeceyorumluyor%')
            ->orWhere('title', 'like', '%sadeceyorumluyor%')
            ->get();
        foreach ($unverified as $uch) {
            $vids = Video::where('youtube_channel_id', $uch->id)->pluck('id');
            if ($vids->isNotEmpty()) {
                UserVideoSelection::whereIn('video_id', $vids)->delete();
            }
            Video::where('youtube_channel_id', $uch->id)->delete();
            UserChannelLink::where('youtube_channel_id', $uch->id)->delete();
            $uch->delete();
        }

        // 1. Kullanıcının oturumundaki, profiline kayıtlı ve geçmişte bağladığı tüm kanalları topla
        $sessionIds = (array) session('available_youtube_channels', []);
        $storedIds = (array) ($user->google_channel_ids ?? []);
        $linkedIds = $user->channelLinks()->with('channel')->get()->pluck('channel.youtube_channel_id')->filter()->all();
        $ids = array_values(array_unique(array_filter(array_merge($sessionIds, $storedIds, $linkedIds))));

        // 2. Eğer kayıtlı liste yoksa kullanıcının OAuth token'ı üzerinden doğrulanmış kanallarını çekmeyi dene
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
                            $ch = YouTubeChannel::updateOrCreate(
                                ['youtube_channel_id' => $item['youtube_channel_id']],
                                collect($item)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                            );
                            try {
                                $channelsService->syncChannelVideos($ch);
                            } catch (\Throwable) {}
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

        // Kullanıcının doğrulanmış kanalı var ama aktif kanalı boşsa ilkini aktif yap
        if (!$active && $channels->isNotEmpty()) {
            $firstCh = $channels->first();
            $existingLink = UserChannelLink::where('user_id', $user->id)
                ->where('youtube_channel_id', $firstCh->id)
                ->latest('id')
                ->first();
            if ($existingLink) {
                $existingLink->update(['ended_at' => null, 'started_at' => now()]);
                $active = $existingLink->fresh(['channel']);
            } else {
                $active = UserChannelLink::create([
                    'user_id'            => $user->id,
                    'youtube_channel_id' => $firstCh->id,
                    'started_at'         => now(),
                    'ended_at'           => null,
                ]);
                $active->load('channel');
            }
        }

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
     * Google OAuth penceresinden gelen erişim belirteci ile kullanıcının doğrulanmış YouTube kanallarını çeker
     * ve mevcut üyeye (yeni üye açmadan!) bağlar.
     */
    public function syncGoogleChannels(Request $request, YouTubeChannelService $channelsService): JsonResponse
    {
        $accessToken = (string) $request->input('accessToken', '');
        if (empty($accessToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Google yetkilendirme belirteci alınamadı. Lütfen açılan pencerede YouTube erişim iznini onaylayın.'
            ], 422);
        }

        try {
            $fetched = $channelsService->listMine($accessToken);

            if ($fetched->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seçilen Google hesabına ait doğrulanmış bir YouTube kanalı bulunamadı veya YouTube erişim izni onaylanmadı.'
                ]);
            }

            $user = $request->user();
            $newIds = [];
            $firstChannel = null;

            foreach ($fetched as $data) {
                $ch = YouTubeChannel::updateOrCreate(
                    ['youtube_channel_id' => $data['youtube_channel_id']],
                    collect($data)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                );
                $newIds[] = $data['youtube_channel_id'];
                if (!$firstChannel) {
                    $firstChannel = $ch;
                }

                try {
                    $channelsService->syncChannelVideos($ch);
                } catch (\Throwable) {}
            }

            $currentIds = (array) ($user->google_channel_ids ?? []);
            $mergedIds = array_values(array_unique(array_filter(array_merge($currentIds, $newIds))));

            session(['available_youtube_channels' => $mergedIds]);
            $user->update(['google_channel_ids' => $mergedIds]);

            // Kullanıcının aktif kanalı yoksa ilk kanalı aktif yap
            if (!$user->activeChannelLink()->exists() && $firstChannel) {
                UserChannelLink::where('youtube_channel_id', $firstChannel->id)
                    ->whereNull('ended_at')
                    ->where('user_id', '<>', $user->id)
                    ->update(['ended_at' => now()]);

                $existingLink = UserChannelLink::where('user_id', $user->id)
                    ->where('youtube_channel_id', $firstChannel->id)
                    ->latest('id')
                    ->first();

                if ($existingLink) {
                    $existingLink->update(['ended_at' => null, 'started_at' => now()]);
                } else {
                    UserChannelLink::create([
                        'user_id'            => $user->id,
                        'youtube_channel_id' => $firstChannel->id,
                        'started_at'         => now(),
                        'ended_at'           => null,
                    ]);
                }
            }

            // Diğer tüm kanalları da kullanıcıya bağla
            foreach ($newIds as $ytId) {
                $ch = YouTubeChannel::where('youtube_channel_id', $ytId)->first();
                if ($ch && !UserChannelLink::where('user_id', $user->id)->where('youtube_channel_id', $ch->id)->exists()) {
                    UserChannelLink::create([
                        'user_id'            => $user->id,
                        'youtube_channel_id' => $ch->id,
                        'started_at'         => now(),
                        'ended_at'           => now(),
                    ]);
                }
            }

            $allChannels = YouTubeChannel::whereIn('youtube_channel_id', $mergedIds)->orderBy('title')->get();
            $addedTitle = $firstChannel ? "“{$firstChannel->title}”" : count($newIds) . ' kanal';

            session()->flash('status', "{$addedTitle} ve son videoları hesabınıza başarıyla eklendi! Aşağıdan yönetmek istediğiniz kanalı seçebilirsiniz.");

            return response()->json([
                'success'  => true,
                'reload'   => true,
                'channels' => $allChannels->values(),
                'message'  => "{$addedTitle} ve son videoları başarıyla hesabınıza eklendi!"
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

        // 1. Yöntem: Kanal bağlantısı veya kullanıcı adı (@kullaniciadi — tekli veya virgül/satır ile çoklu) girilmişse
        if ($request->filled('channel_input')) {
            $input = trim((string) $request->input('channel_input'));
            $rawInputs = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $input))));

            $resolvedList = [];
            foreach ($rawInputs as $singleInput) {
                $res = $this->resolveYouTubeChannel($singleInput);
                if ($res && !empty($res['channel_id'])) {
                    $resolvedList[] = $res;
                }
            }

            if (empty($resolvedList)) {
                return back()->withErrors([
                    'channel_input' => 'YouTube kanalı tespit edilemedi. Lütfen geçerli bir kanal kullanıcı adı (@kanaladi) veya linki girin.'
                ])->withInput();
            }

            try {
                $importedChannels = [];
                $firstChannel = null;

                DB::transaction(function () use ($user, $active, $resolvedList, &$importedChannels, &$firstChannel) {
                    foreach ($resolvedList as $idx => $resolved) {
                        $ch = YouTubeChannel::updateOrCreate(
                            ['youtube_channel_id' => $resolved['channel_id']],
                            [
                                'title'          => $resolved['title'] ?: ($user->name . ' YouTube Kanalı'),
                                'thumbnail_url'  => $resolved['thumbnail_url'] ?: $user->avatar_url,
                                'custom_url'     => $resolved['custom_url'] ?? null,
                                'last_synced_at' => now(),
                            ]
                        );

                        // Başka bir aktif kullanıcıya bağlı mı?
                        if (UserChannelLink::where('youtube_channel_id', $ch->id)->whereNull('ended_at')->where('user_id', '<>', $user->id)->exists()) {
                            throw new \DomainException("“{$ch->title}” kanalı halihazırda başka bir platform üyesine bağlı.");
                        }

                        $importedChannels[] = $ch;
                        if ($idx === 0) {
                            $firstChannel = $ch;
                        }
                    }

                    if ($firstChannel) {
                        if (!$active || $active->youtube_channel_id !== $firstChannel->id) {
                            UserChannelLink::where('user_id', $user->id)->whereNull('ended_at')->update(['ended_at' => now()]);
                            UserChannelLink::create([
                                'user_id'            => $user->id,
                                'youtube_channel_id' => $firstChannel->id,
                                'started_at'         => now(),
                            ]);
                        }
                    }

                    foreach ($importedChannels as $ch) {
                        if (!UserChannelLink::where('user_id', $user->id)->where('youtube_channel_id', $ch->id)->exists()) {
                            UserChannelLink::create([
                                'user_id'            => $user->id,
                                'youtube_channel_id' => $ch->id,
                                'started_at'         => now(),
                                'ended_at'           => now(),
                            ]);
                        }
                    }
                });

                $currentIds = (array) ($user->google_channel_ids ?? []);
                foreach ($importedChannels as $ch) {
                    try {
                        $channelsService->syncChannelVideos($ch);
                    } catch (\Throwable $syncEx) {
                        \Log::warning('syncChannelVideos atlandı: ' . $syncEx->getMessage());
                    }

                    if (!in_array($ch->youtube_channel_id, $currentIds, true)) {
                        $currentIds[] = $ch->youtube_channel_id;
                    }
                }
                $user->update(['google_channel_ids' => array_values(array_unique($currentIds))]);
            } catch (\DomainException $e) {
                return back()->withErrors(['channel_input' => $e->getMessage()])->withInput();
            } catch (\Throwable $e) {
                report($e);
                return back()->withErrors(['channel_input' => 'Kanal kaydedilirken hata oluştu: ' . $e->getMessage()])->withInput();
            }

            session()->forget('available_youtube_channels');

            if (count($importedChannels) > 1) {
                return redirect()->route('channels.select')->with('status', count($importedChannels) . ' YouTube kanalınız ve son videoları başarıyla sisteme çekildi! Şimdi yönetmek istediğiniz kanalı seçip vitrin videolarını belirleyebilirsiniz.');
            }

            $channel = $firstChannel;
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
            return $this->fallbackSearchChannel($input);
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
            return $this->fallbackSearchChannel($input);
        }

        return [
            'channel_id' => $channelId,
            'title' => html_entity_decode($title ?: 'YouTube Kanalı', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'thumbnail_url' => $thumbnailUrl,
            'custom_url' => $customUrl,
        ];
    }

    private function fallbackSearchChannel(string $input): ?array
    {
        try {
            $found = app(YouTubeChannelService::class)->resolveBestChannelByName($input);
            if ($found && !empty($found['youtube_channel_id'])) {
                return [
                    'channel_id'    => $found['youtube_channel_id'],
                    'title'         => $found['title'] ?? 'YouTube Kanalı',
                    'thumbnail_url' => $found['thumbnail_url'] ?? null,
                    'custom_url'    => $found['custom_url'] ?? null,
                ];
            }
        } catch (\Throwable) {}
        return null;
    }
}
