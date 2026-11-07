<?php

namespace App\Http\Controllers;

use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VideoShowcaseController extends Controller
{
    public function index(Request $request, \App\Services\YouTubeChannelService $channelsService): View|RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        if (!$channel) {
            return redirect()->route('channels.select')
                ->withErrors(['channel' => 'Video vitrini için önce bir YouTube kanalı bağlamalısınız.']);
        }

        // Kanalın kayıtlı videosu yoksa otomatik olarak son 10 videosunu çek
        $existingCount = Video::where('youtube_channel_id', $channel->id)->count();
        if ($existingCount === 0) {
            $channelsService->syncChannelVideos($channel);
        }

        // Kanalın en son 10 videosu listelenir
        $channelVideos = Video::where('youtube_channel_id', $channel->id)
            ->where('availability', 'available')
            ->latest('published_at')
            ->limit(10)
            ->get();

        // YALNIZCA bu kanala ait vitrinde seçili video ID'leri (en fazla 3 adet)
        $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
        $selectedIds = UserVideoSelection::where('user_id', $user->id)
            ->where(function ($q) use ($channel, $channelVideoIds) {
                $q->where('youtube_channel_id', $channel->id)
                  ->orWhereIn('video_id', $channelVideoIds);
            })
            ->pluck('video_id')
            ->all();

        // Vitrinde yayında olan videolar
        $showcaseVideos = Video::whereIn('id', $selectedIds)
            ->where('availability', 'available')
            ->get();

        // Kullanıcının kayıtlı tüm kanalları (hızlı kanal değiştirici için)
        $allChannelIds = array_values(array_unique(array_filter(array_merge(
            (array) ($user->google_channel_ids ?? []),
            $user->channelLinks()->with('channel')->get()->pluck('channel.youtube_channel_id')->filter()->all()
        ))));
        $userChannels = !empty($allChannelIds)
            ? YouTubeChannel::whereIn('youtube_channel_id', $allChannelIds)->orderBy('title')->get()
            : collect([$channel]);

        foreach ($userChannels as $uc) {
            $ucVideoIds = Video::where('youtube_channel_id', $uc->id)->pluck('id');
            $uc->showcase_count = UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($uc, $ucVideoIds) {
                    $q->where('youtube_channel_id', $uc->id)
                      ->orWhereIn('video_id', $ucVideoIds);
                })->count();
        }

        return view('videos.showcase', compact('channelVideos', 'selectedIds', 'showcaseVideos', 'channel', 'userChannels'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        abort_unless($channel, 422, 'Aktif kanal bulunamadı.');

        $data = $request->validate([
            'video_ids'   => ['nullable', 'array', 'max:3'],
            'video_ids.*' => ['integer', 'distinct', 'exists:videos,id'],
        ], [
            'video_ids.max' => 'Vitrinde en fazla 3 video seçebilirsiniz.',
        ]);

        $ids = array_map('intval', $data['video_ids'] ?? []);
        $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');

        // Eğer hiç video seçilmemişse SADECE bu kanalın vitrini boşaltılır
        if (empty($ids)) {
            UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($channel, $channelVideoIds) {
                    $q->where('youtube_channel_id', $channel->id)
                      ->orWhereIn('video_id', $channelVideoIds);
                })->delete();

            return redirect()->route('videos.showcase')
                ->with('status', '“' . $channel->title . '” vitrininiz boşaltıldı.');
        }

        DB::transaction(function () use ($user, $channel, $channelVideoIds, $ids) {
            // SADECE bu kanala ait vitrin seçimlerini güncelle
            UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($channel, $channelVideoIds) {
                    $q->where('youtube_channel_id', $channel->id)
                      ->orWhereIn('video_id', $channelVideoIds);
                })->delete();

            foreach ($ids as $videoId) {
                UserVideoSelection::create([
                    'user_id'            => $user->id,
                    'video_id'           => $videoId,
                    'youtube_channel_id' => $channel->id,
                ]);
            }
        });

        $count = count($ids);
        $message = $count === 3
            ? '3 video “' . $channel->title . '” vitrininize kaydedildi! Kanalınız ve videolarınız artık keşif havuzunda yayında.'
            : $count . ' video kaydedildi. Kanalınızın keşif havuzuna girmesi için 3 video seçilmelidir (' . $count . '/3).';

        return redirect()->route('videos.showcase')->with('status', $message);
    }

    /**
     * Tekil bir videoyu doğrudan vitrine ekler.
     */
    public function addSingle(Request $request, Video $video): RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        abort_unless($channel, 422, 'Aktif kanal bulunamadı.');
        abort_unless($video->youtube_channel_id === $channel->id, 403, 'Bu video kanalınıza ait değil.');

        $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
        $currentCount = UserVideoSelection::where('user_id', $user->id)
            ->where(function ($q) use ($channel, $channelVideoIds) {
                $q->where('youtube_channel_id', $channel->id)
                  ->orWhereIn('video_id', $channelVideoIds);
            })->count();

        if ($currentCount >= 3) {
            return back()->withErrors(['error' => 'Bu kanalın vitrininde en fazla 3 video yer alabilir.']);
        }

        UserVideoSelection::firstOrCreate(
            [
                'user_id'  => $user->id,
                'video_id' => $video->id,
            ],
            [
                'youtube_channel_id' => $channel->id,
            ]
        );

        return back()->with('status', '“' . $video->title . '” videosu “' . $channel->title . '” vitrininize eklendi! (' . ($currentCount + 1) . '/3)');
    }

    /**
     * Vitrindeki tekil bir videoyu kaldırır.
     */
    public function removeSingle(Request $request, Video $video): RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        abort_unless($channel, 422, 'Aktif kanal bulunamadı.');

        $deleted = UserVideoSelection::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->delete();

        if ($deleted) {
            return back()->with('status', '“' . $video->title . '” videosu vitrininizden kaldırıldı.');
        }

        return back()->withErrors(['error' => 'Bu video vitrininizde bulunamadı.']);
    }

    /**
     * Vitrindeki tüm videoları sıfırlar / kaldırır (yalnızca aktif kanal için).
     */
    public function clearAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        if ($channel) {
            $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
            UserVideoSelection::where('user_id', $user->id)
                ->where(function ($q) use ($channel, $channelVideoIds) {
                    $q->where('youtube_channel_id', $channel->id)
                      ->orWhereIn('video_id', $channelVideoIds);
                })->delete();

            return back()->with('status', '“' . $channel->title . '” vitrininizdeki tüm videolar kaldırıldı.');
        }

        UserVideoSelection::where('user_id', $user->id)->delete();

        return back()->with('status', 'Vitrininizdeki tüm videolar kaldırıldı.');
    }

    /**
     * Kanalın son 10 videosunu YouTube'dan yeniden senkronize eder.
     */
    public function syncChannelVideos(Request $request, \App\Services\YouTubeChannelService $channelsService): RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        if (!$channel) {
            return back()->withErrors(['error' => 'Bağlı bir kanalınız bulunamadı.']);
        }

        $added = $channelsService->syncChannelVideos($channel);

        if ($added > 0) {
            return redirect()->route('videos.showcase')
                ->with('status', "Kanalınızdan {$added} adet video başarıyla güncellendi! Aşağıdan dilediğiniz 3 videoyu seçebilirsiniz.");
        }

        return redirect()->route('videos.showcase')
            ->with('status', 'Kanal videolarınız güncel.');
    }

    /**
     * Link veya ID girilerek doğrudan video ekleme.
     */
    public function addVideoByUrl(Request $request): RedirectResponse
    {
        $request->validate([
            'video_url' => ['required', 'string']
        ]);

        $url = trim($request->input('video_url'));
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        if (!$channel) {
            return back()->withErrors(['video_url' => 'Bağlı bir kanalınız bulunamadı.']);
        }

        $channelVideoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
        $currentCount = UserVideoSelection::where('user_id', $user->id)
            ->where(function ($q) use ($channel, $channelVideoIds) {
                $q->where('youtube_channel_id', $channel->id)
                  ->orWhereIn('video_id', $channelVideoIds);
            })->count();

        if ($currentCount >= 3) {
            return back()->withErrors(['video_url' => '“' . $channel->title . '” kanalınızın vitrini dolu (3/3). Yeni video eklemek için önce bir video çıkarın.'])->withInput();
        }

        // Shorts URL'ini reddet
        if (str_contains($url, '/shorts/')) {
            return back()->withErrors(['video_url' => 'YouTube Shorts videoları eklenemez. Yalnızca normal (yatay) videolar kabul edilir.'])->withInput();
        }

        $videoId = null;
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/^[\w-]{11}$/', $url)) {
            $videoId = $url;
        }

        if (!$videoId) {
            return back()->withErrors(['video_url' => 'Geçersiz YouTube video bağlantısı. Örn: https://www.youtube.com/watch?v=...'])->withInput();
        }

        // Önce DB'de bu video var mı ve başka kanala mı ait kontrol et
        $existing = Video::where('youtube_video_id', $videoId)->first();
        if ($existing && $existing->youtube_channel_id !== $channel->id) {
            return back()->withErrors(['video_url' => 'Bu video başka bir kanala ait, ekleyemezsiniz.'])->withInput();
        }

        $title = 'Video ' . $videoId;
        $thumb = 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg';
        $verifiedOwnership = false;

        // 1. YouTube Data API v3 ile kontrol
        $apiKey = config('services.youtube.api_key') ?: env('YOUTUBE_API_KEY');
        if ($apiKey) {
            $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$videoId}&key={$apiKey}";
            $chApi = curl_init($apiUrl);
            curl_setopt_array($chApi, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $apiRaw = curl_exec($chApi);
            $apiCode = curl_getinfo($chApi, CURLINFO_HTTP_CODE);

            if ($apiRaw && $apiCode === 200) {
                $apiData = json_decode($apiRaw, true);
                if (!empty($apiData['items'][0]['snippet'])) {
                    $snippet = $apiData['items'][0]['snippet'];
                    $apiChannelId = $snippet['channelId'] ?? null;
                    if ($apiChannelId && $apiChannelId !== $channel->youtube_channel_id) {
                        return back()->withErrors(['video_url' => 'Bu video bağlı YouTube kanalınıza ait görünmüyor. Yalnızca kendi kanalınızdaki videoları vitrine ekleyebilirsiniz.'])->withInput();
                    }
                    $verifiedOwnership = true;
                    $title = $snippet['title'] ?? $title;
                    $thumb = $snippet['thumbnails']['high']['url'] ?? ($snippet['thumbnails']['medium']['url'] ?? $thumb);
                }
            }
        }

        // 2. oEmbed ile fallback
        if (!$verifiedOwnership) {
            $oembedUrl = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $videoId . '&format=json';
            $ch = curl_init($oembedUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $oembedRaw = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (!$oembedRaw || $httpCode !== 200) {
                return back()->withErrors(['video_url' => 'Video bilgileri alınamadı. Video herkese açık olmayabilir.'])->withInput();
            }

            $oembedData = json_decode($oembedRaw, true);
            $authorName = mb_strtolower(trim($oembedData['author_name'] ?? ''));
            $channelTitle = mb_strtolower(trim($channel->title ?? ''));

            if ($authorName && $channelTitle && $authorName !== $channelTitle && !str_contains($authorName, $channelTitle) && !str_contains($channelTitle, $authorName)) {
                return back()->withErrors(['video_url' => "Bu video '{$channel->title}' kanalına ait görünmüyor."])->withInput();
            }

            $title = $oembedData['title'] ?? $title;
            $thumb = $oembedData['thumbnail_url'] ?? $thumb;
        }

        $video = Video::updateOrCreate(
            ['youtube_video_id' => $videoId],
            [
                'youtube_channel_id' => $channel->id,
                'title'              => $title,
                'thumbnail_url'      => $thumb,
                'published_at'       => now(),
                'availability'       => 'available',
                'last_checked_at'    => now(),
            ]
        );

        UserVideoSelection::firstOrCreate(
            [
                'user_id'  => $user->id,
                'video_id' => $video->id,
            ],
            [
                'youtube_channel_id' => $channel->id,
            ]
        );

        return redirect()->route('videos.showcase')
            ->with('status', '“' . $title . '” videosu “' . $channel->title . '” vitrininize eklendi! (' . ($currentCount + 1) . '/3)');
    }

    /**
     * Kanalın son 10 videosunu çeken yardımcı fonksiyon.
     */
    protected function syncChannelLatestVideos(YouTubeChannel $channel): int
    {
        $apiKey = config('services.youtube.api_key') ?: env('YOUTUBE_API_KEY');
        $added = 0;

        if ($apiKey && $channel->youtube_channel_id) {
            $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=" . urlencode($channel->youtube_channel_id) . "&order=date&type=video&maxResults=10&key=" . urlencode($apiKey);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $raw = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($raw && $code === 200) {
                $data = json_decode($raw, true);
                foreach ($data['items'] ?? [] as $item) {
                    $vid = $item['id']['videoId'] ?? null;
                    if (!$vid) continue;

                    $snippet = $item['snippet'] ?? [];
                    $title = $snippet['title'] ?? ('Video ' . $vid);
                    $thumb = $snippet['thumbnails']['high']['url'] ?? ($snippet['thumbnails']['medium']['url'] ?? "https://i.ytimg.com/vi/{$vid}/hqdefault.jpg");
                    $publishedAt = isset($snippet['publishedAt']) ? \Carbon\Carbon::parse($snippet['publishedAt']) : now();

                    Video::updateOrCreate(
                        ['youtube_video_id' => $vid],
                        [
                            'youtube_channel_id' => $channel->id,
                            'title'              => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                            'thumbnail_url'      => $thumb,
                            'published_at'       => $publishedAt,
                            'availability'       => 'available',
                            'last_checked_at'    => now(),
                        ]
                    );
                    $added++;
                }
                if ($added > 0) return $added;
            }
        }

        // Web scraping fallback
        $targetUrl = $channel->custom_url
            ? 'https://www.youtube.com/' . ltrim($channel->custom_url, '/') . '/videos'
            : 'https://www.youtube.com/channel/' . $channel->youtube_channel_id . '/videos';

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $html = curl_exec($ch);
        if ($html) {
            preg_match_all('/"videoId":"([a-zA-Z0-9_-]{11})"/', $html, $matches);
            preg_match_all('#/shorts/([a-zA-Z0-9_-]{11})#', $html, $shortsMatches);
            $shortsIds = array_flip($shortsMatches[1] ?? []);
            $videoIds = array_slice(array_values(array_filter(array_unique($matches[1] ?? []), fn($id) => !isset($shortsIds[$id]))), 0, 10);

            foreach ($videoIds as $vid) {
                Video::updateOrCreate(
                    ['youtube_video_id' => $vid],
                    [
                        'youtube_channel_id' => $channel->id,
                        'title'              => 'Video ' . $vid,
                        'thumbnail_url'      => "https://i.ytimg.com/vi/{$vid}/hqdefault.jpg",
                        'published_at'       => now(),
                        'availability'       => 'available',
                        'last_checked_at'    => now(),
                    ]
                );
                $added++;
            }
        }

        return $added;
    }
}
