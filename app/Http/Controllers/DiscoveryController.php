<?php

namespace App\Http\Controllers;

use App\Models\PlatformFollow;
use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DiscoveryController extends Controller
{
    /**
     * Kullanıcı / Kanal Keşfi:
     * Platformdaki her kullanıcı tarafından eklenen kanallar bağımsız birer üretici olarak listelenir.
     */
    public function users(Request $request, string $pool = 'normal'): View
    {
        abort_unless(in_array($pool, ['normal', 'supporter'], true), 404);
        $me = $request->user();

        // 1. Giriş yapan kullanıcının kendi kanallarını topla (kendi kanalını keşifte görmemeli)
        $myLinkedChannelIds = UserChannelLink::where('user_id', $me->id)->pluck('youtube_channel_id')->all();
        $myGoogleChannelIds = YouTubeChannel::whereIn('youtube_channel_id', (array) ($me->google_channel_ids ?? []))->pluck('id')->all();
        $myChannelIds = array_values(array_unique(array_filter(array_merge($myLinkedChannelIds, $myGoogleChannelIds))));

        $userChannels = !empty($myChannelIds)
            ? YouTubeChannel::whereIn('id', $myChannelIds)->orderBy('title')->get()
            : collect();

        $activeChannel = $me->activeChannelLink()->with('channel')->first()?->channel;
        if ($request->filled('source_channel_id')) {
            $requestedCh = $userChannels->firstWhere('id', (int) $request->input('source_channel_id'));
            if ($requestedCh) {
                $activeChannel = $requestedCh;
            }
        } elseif (!$activeChannel && $userChannels->isNotEmpty()) {
            $activeChannel = $userChannels->first();
        }

        $sourceChannelId = $activeChannel?->id;

        foreach ($userChannels as $uc) {
            $uc->follows_today = PlatformFollow::where('follower_user_id', $me->id)
                ->where('follower_channel_id', $uc->id)
                ->whereDate('created_at', today())
                ->count();
        }

        $followsToday = $sourceChannelId
            ? PlatformFollow::where('follower_user_id', $me->id)
                ->where('follower_channel_id', $sourceChannelId)
                ->whereDate('created_at', today())
                ->count()
            : PlatformFollow::where('follower_user_id', $me->id)
                ->whereDate('created_at', today())
                ->count();

        // 2. Bu kaynak kanal tarafından halihazırda takip edilen kanal ID'leri
        $followedQuery = PlatformFollow::where('follower_user_id', $me->id)
            ->whereNotNull('youtube_channel_id');
        if ($sourceChannelId) {
            $followedQuery->where(function ($q) use ($sourceChannelId) {
                $q->where('follower_channel_id', $sourceChannelId)
                  ->orWhereNull('follower_channel_id');
            });
        }
        $followedChannelIds = $followedQuery->pluck('youtube_channel_id')->all();

        // 3. Engellenen kullanıcılar
        $blockedByMe = DB::table('user_blocks')->where('blocker_user_id', $me->id)->pluck('blocked_user_id')->all();
        $blockedMe = DB::table('user_blocks')->where('blocked_user_id', $me->id)->pluck('blocker_user_id')->all();
        $blockedUserIds = array_values(array_unique(array_merge($blockedByMe, $blockedMe)));

        // 4. Havuzdaki tüm kanallar sorgusu
        $baseQuery = YouTubeChannel::query()
            ->whereNotIn('id', $myChannelIds);

        if (!empty($followedChannelIds)) {
            $baseQuery->whereNotIn('id', $followedChannelIds);
        }

        // Engellenen kullanıcıların kanallarını hariç tut
        if (!empty($blockedUserIds)) {
            $baseQuery->whereDoesntHave('links', function ($q) use ($blockedUserIds) {
                $q->whereIn('user_id', $blockedUserIds);
            });
        }

        // Destekçi havuzu / Normal havuz filtrelemesi
        if ($pool === 'supporter') {
            $baseQuery->whereHas('links.user.supporterStatuses', function ($q) {
                $q->where('status', 'active')->where('ends_at', '>', now());
            });
        } else {
            $baseQuery->whereDoesntHave('links.user.supporterStatuses', function ($q) {
                $q->where('status', 'active')->where('ends_at', '>', now());
            });
        }

        // Gösterim (impression) takibi (oturum bazlı kanal takibi)
        $seenChannelIds = (array) session("seen_channel_ids_{$pool}", []);

        $channels = (clone $baseQuery)
            ->whereNotIn('id', $seenChannelIds)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        if ($channels->isEmpty()) {
            session()->forget("seen_channel_ids_{$pool}");
            $channels = (clone $baseQuery)->inRandomOrder()->limit(10)->get();
        }

        $newSeen = array_values(array_unique(array_merge($seenChannelIds, $channels->pluck('id')->all())));
        session(["seen_channel_ids_{$pool}" => $newSeen]);

        $channels->load(['links.user']);

        // Görüntülenen kanalların sahiplerini impression tablosuna kaydet
        $impressionRows = $channels->map(function ($ch) use ($me, $pool, $sourceChannelId) {
            $ownerId = $ch->links->first()?->user_id;
            if (!$ownerId) return null;
            return [
                'viewer_user_id'    => $me->id,
                'viewer_channel_id' => $sourceChannelId,
                'shown_user_id'     => $ownerId,
                'pool_type'         => $pool,
                'shown_at'          => now(),
            ];
        })->filter()->values()->all();

        if (!empty($impressionRows)) {
            try {
                DB::table('user_impressions')->insert($impressionRows);
            } catch (\Throwable) {}
        }

        // Hem $channels hem de $users değişkenleri görünümle paylaşılır (tam geriye dönük uyumluluk)
        $users = $channels->map(function ($ch) {
            $owner = $ch->links->first()?->user;
            return (object) [
                'id'                => $ch->id,
                'channel_id'        => $ch->id,
                'user_id'           => $owner?->id,
                'name'              => $ch->title,
                'username'          => $ch->custom_url ? ltrim($ch->custom_url, '@') : ($owner?->username ?? 'kanal'),
                'activeChannelLink' => (object) [
                    'channel' => $ch,
                ],
            ];
        });

        return view('discovery.users', compact('channels', 'users', 'pool', 'activeChannel', 'userChannels', 'followsToday'));
    }

    /**
     * Seçilen 3 kanalı / üreticiyi takip etme işlemi (Her kanal için ayrı günlük 3 görev kotası)
     */
    public function follow(Request $request, string $pool): RedirectResponse
    {
        abort_unless(in_array($pool, ['normal', 'supporter'], true), 404);
        $me = $request->user();

        // Kaynak kanal belirleme (kullanıcının birden fazla kanalı varsa her kanalın ayrı günlük takip görevi vardır)
        $myLinkedChannelIds = UserChannelLink::where('user_id', $me->id)->pluck('youtube_channel_id')->all();
        $myGoogleChannelIds = YouTubeChannel::whereIn('youtube_channel_id', (array) ($me->google_channel_ids ?? []))->pluck('id')->all();
        $myChannelIds = array_values(array_unique(array_filter(array_merge($myLinkedChannelIds, $myGoogleChannelIds))));

        $sourceChannelId = $request->filled('source_channel_id')
            ? (int) $request->input('source_channel_id')
            : $me->activeChannelLink()->value('youtube_channel_id');

        if ($sourceChannelId && !in_array($sourceChannelId, $myChannelIds, true)) {
            abort(403, 'Bu kanal hesabınıza ait değil.');
        }

        // Hem channel_ids hem user_ids dizilerini destekle
        $rawIds = $request->input('channel_ids') ?? $request->input('user_ids');
        if (!is_array($rawIds) || count($rawIds) !== 3) {
            return back()->withErrors(['error' => 'Lütfen takip etmek için tam olarak 3 kanal seçin.']);
        }

        $ids = array_values(array_unique(array_map('intval', $rawIds)));
        if (count($ids) !== 3) {
            return back()->withErrors(['error' => 'Lütfen birbirinden farklı 3 kanal seçin.']);
        }

        // Günlük sınır kontrolü: Her kanalın günlük 3 takip etme / abone olma görevi vardır
        $todayQuery = PlatformFollow::where('follower_user_id', $me->id)
            ->whereDate('created_at', today());
        if ($sourceChannelId) {
            $todayQuery->where(function ($q) use ($sourceChannelId) {
                $q->where('follower_channel_id', $sourceChannelId)
                  ->orWhereNull('follower_channel_id');
            });
        }
        $todayFollows = $todayQuery->count();

        abort_if($todayFollows >= 3, 429, 'Bu kanal için günlük 3 kanal takip etme görevinizi tamamladınız. Diğer kanalınıza geçebilir veya yarın tekrar keşfe çıkabilirsiniz.');

        $redirectParams = $request->filled('source_channel_id')
            ? ['pool' => $pool, 'source_channel_id' => $sourceChannelId]
            : $pool;

        // İster kanal ID'si ister kullanıcı ID'si gelmiş olsun çözümle
        $channels = YouTubeChannel::with('links.user')->whereIn('id', $ids)->get();

        // Eğer ID'ler kanal tablosunda bulunamadıysa users tablosunda ara (eski testler için)
        if ($channels->count() < 3) {
            $fallbackUsers = User::with('activeChannelLink.channel')->whereIn('id', $ids)->get();
            if ($fallbackUsers->count() === 3) {
                DB::transaction(function () use ($fallbackUsers, $me, $pool, $sourceChannelId) {
                    foreach ($fallbackUsers as $u) {
                        PlatformFollow::firstOrCreate(
                            [
                                'follower_user_id'    => $me->id,
                                'follower_channel_id' => $sourceChannelId,
                                'followed_user_id'    => $u->id,
                            ],
                            [
                                'youtube_channel_id' => $u->activeChannelLink?->channel?->id,
                                'pool_type'          => $pool,
                                'created_at'         => now(),
                            ]
                        );
                    }
                });
                return redirect()->route('discovery.users', $redirectParams)->with('status', 'Platform takipleriniz kaydedildi.');
            }
            abort(422, 'Seçimleriniz keşif havuzu kurallarına uymuyor.');
        }

        DB::transaction(function () use ($channels, $me, $pool, $sourceChannelId) {
            foreach ($channels as $ch) {
                $owner = $ch->links->first()?->user;
                $followedUserId = $owner?->id ?? $me->id;

                PlatformFollow::firstOrCreate(
                    [
                        'follower_user_id'    => $me->id,
                        'follower_channel_id' => $sourceChannelId,
                        'youtube_channel_id'  => $ch->id,
                    ],
                    [
                        'followed_user_id'   => $followedUserId,
                        'pool_type'          => $pool,
                        'created_at'         => now(),
                    ]
                );
            }
        });

        return redirect()->route('discovery.users', $redirectParams)->with('status', 'Seçtiğiniz 3 kanal başarıyla takip edildi!');
    }

    /**
     * Video Keşfi:
     * 1. Kullanıcıların aktif kanal ve hesaplarına ait vitrin videoları burada listelenir.
     * 2. Kullanıcının birden fazla kanalı varsa (örn. 4 kanal), o an AKTİF olan kanalı hariç tutulur;
     *    diğer kanallarına ve diğer kullanıcılara ait vitrin videoları keşif havuzunda yer alır.
     * 3. 10 video gösterilir -> Kullanıcı 3 video seçer -> Seçilen 3 video sırayla oynatılır.
     */
    public function videos(Request $request): View
    {
        $me = $request->user();
        $selectedIds = session('selected_video_ids');

        // Kullanıcının o an temsil ettiği aktif kanal
        $activeChannelId = UserChannelLink::where('user_id', $me->id)
            ->whereNull('ended_at')
            ->latest('id')
            ->value('youtube_channel_id');

        // Hariç tutulacak videolar: SADECE kullanıcının şu an aktif olarak temsil ettiği kanalına ait videolar!
        // Kullanıcının diğer alternatif kanallarına ve diğer üreticilere ait vitrin videoları keşfette görünmelidir.
        $excludedChannelIds = $activeChannelId ? [$activeChannelId] : [];
        $excludedVideoIds = $activeChannelId ? Video::where('youtube_channel_id', $activeChannelId)->pluck('id')->all() : [];

        // Engellenen kullanıcılar
        $blockedByMe = DB::table('user_blocks')->where('blocker_user_id', $me->id)->pluck('blocked_user_id')->all();
        $blockedMe = DB::table('user_blocks')->where('blocked_user_id', $me->id)->pluck('blocker_user_id')->all();
        $blockedUserIds = array_values(array_unique(array_merge($blockedByMe, $blockedMe)));

        // 1. Sıralı Oynatıcı Modu: Kullanıcı 3 video seçmişse
        if (is_array($selectedIds) && count($selectedIds) === 3) {
            $selectedQuery = Video::with('channel')
                ->whereIn('id', $selectedIds)
                ->where('availability', 'available');

            if (!empty($excludedChannelIds)) {
                $selectedQuery->whereNotIn('youtube_channel_id', $excludedChannelIds);
            }
            if (!empty($excludedVideoIds)) {
                $selectedQuery->whereNotIn('id', $excludedVideoIds);
            }

            $selectedVideos = $selectedQuery->get();

            if ($selectedVideos->count() === 3) {
                $playlist = $selectedVideos->map(fn($v) => [
                    'id'      => (string) $v->youtube_video_id,
                    'title'   => (string) $v->title,
                    'channel' => (string) ($v->channel?->title ?? 'YouTube Kanalı'),
                ])->values()->all();

                return view('discovery.videos', [
                    'mode'           => 'player',
                    'selectedVideos' => $selectedVideos,
                    'playlist'       => $playlist,
                    'videos'         => $selectedVideos,
                ]);
            }
        }

        // 2. Seçim Modu: SADECE vitrine seçilmiş ve aktif kanallara ait videolar havuzdadır!
        $poolVideoQuery = UserVideoSelection::query();

        if (!empty($excludedChannelIds)) {
            $poolVideoQuery->where(function ($q) use ($excludedChannelIds) {
                $q->whereNull('youtube_channel_id')
                  ->orWhereNotIn('youtube_channel_id', $excludedChannelIds);
            });
        }

        if (!empty($excludedVideoIds)) {
            $poolVideoQuery->whereNotIn('video_id', $excludedVideoIds);
        }

        $poolVideoIds = $poolVideoQuery->pluck('video_id')->unique()->all();

        $query = Video::with('channel')
            ->where('availability', 'available')
            ->whereIn('id', $poolVideoIds);

        if (!empty($excludedChannelIds)) {
            $query->whereNotIn('youtube_channel_id', $excludedChannelIds);
        }

        if (!empty($excludedVideoIds)) {
            $query->whereNotIn('id', $excludedVideoIds);
        }

        if (!empty($blockedUserIds)) {
            $query->whereDoesntHave('channel.links', fn($q) => $q->whereIn('user_id', $blockedUserIds));
        }

        $videos = $query->inRandomOrder()->limit(10)->get();

        return view('discovery.videos', [
            'mode'     => 'selection',
            'videos'   => $videos,
            'playlist' => [],
        ]);
    }

    public function selectVideos(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'video_ids'   => ['required', 'array', 'size:3'],
            'video_ids.*' => ['integer', 'distinct', 'exists:videos,id'],
        ], [
            'video_ids.required' => 'Lütfen 3 video seçin.',
            'video_ids.size'     => 'Sistemde sırayla izlemek için tam olarak 3 video seçmelisiniz.',
        ]);

        $me = $request->user();
        $activeChannelId = UserChannelLink::where('user_id', $me->id)
            ->whereNull('ended_at')
            ->latest('id')
            ->value('youtube_channel_id');

        // Kural denetimi: O an aktif olan kendi kanalının videosunu seçemez
        if ($activeChannelId) {
            $forbiddenCount = Video::whereIn('id', $data['video_ids'])
                ->where('youtube_channel_id', $activeChannelId)
                ->count();

            abort_if($forbiddenCount > 0, 403, 'Aktif olarak temsil ettiğiniz kendi kanalınıza ait videoları keşif listesine ekleyemezsiniz.');
        }

        session(['selected_video_ids' => $data['video_ids']]);

        return redirect()->route('discovery.videos')
            ->with('status', 'Seçtiğiniz 3 video oynatıcıya yüklendi!');
    }

    public function resetVideos(Request $request): RedirectResponse
    {
        session()->forget('selected_video_ids');

        return redirect()->route('discovery.videos')
            ->with('status', 'Video listesi yenilendi. Yeni 3 video seçebilirsiniz.');
    }
}
