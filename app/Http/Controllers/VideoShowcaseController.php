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

        // Kanalın en son 10 videosu listelenir
        $videos = Video::where('youtube_channel_id', $channel->id)
            ->where('availability', 'available')
            ->latest('published_at')
            ->limit(10)
            ->get();

        $selectedIds = UserVideoSelection::where('user_id', $user->id)
            ->pluck('video_id')
            ->all();

        return view('videos.showcase', compact('videos', 'selectedIds', 'channel'));
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

        // Eğer hiç video seçilmemişse vitrin boşaltılır
        if (empty($ids)) {
            UserVideoSelection::where('user_id', $user->id)->delete();
            return redirect()->route('videos.showcase')
                ->with('status', 'Vitrininiz boşaltıldı. Dilediğiniz zaman yeni videolar seçebilirsiniz.');
        }

        // Kanalın son 10 videosu arasından seçim yapıldığı doğrulanır
        $allowedIds = Video::where('youtube_channel_id', $channel->id)
            ->where('availability', 'available')
            ->latest('published_at')
            ->limit(10)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        foreach ($ids as $selectedId) {
            if (!in_array($selectedId, $allowedIds, true)) {
                return back()->withErrors(['video_ids' => 'Yalnızca kanalınızın son 10 videosu arasından seçim yapabilirsiniz.']);
            }
        }

        DB::transaction(function () use ($user, $ids) {
            UserVideoSelection::where('user_id', $user->id)->delete();
            foreach ($ids as $videoId) {
                UserVideoSelection::create(['user_id' => $user->id, 'video_id' => $videoId]);
            }
        });

        return redirect()->route('videos.showcase')
            ->with('status', count($ids) . ' video vitrininize başarıyla kaydedildi! (1-3 video hakkınızdan ' . count($ids) . ' tanesini kullandınız)');
    }

    /**
     * Vitrindeki tekil bir videoyu vitrinden kaldırır.
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
     * Tekil bir videoyu doğrudan vitrine ekler.
     */
    public function addSingle(Request $request, Video $video): RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        abort_unless($channel, 422, 'Aktif kanal bulunamadı.');
        abort_unless($video->youtube_channel_id === $channel->id, 403, 'Bu video kanalınıza ait değil.');

        $currentCount = UserVideoSelection::where('user_id', $user->id)->count();
        if ($currentCount >= 3) {
            return back()->withErrors(['error' => 'Vitrininizde en fazla 3 video yer alabilir. Yeni bir video eklemek için önce mevcut bir videoyu kaldırın.']);
        }

        UserVideoSelection::firstOrCreate([
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        return back()->with('status', '“' . $video->title . '” videosu vitrininize eklendi! (Kullanılan hak: ' . ($currentCount + 1) . '/3)');
    }

    /**
     * Vitrindeki tüm videoları sıfırlar / kaldırır.
     */
    public function clearAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        UserVideoSelection::where('user_id', $user->id)->delete();

        return back()->with('status', 'Vitrininizdeki tüm videolar kaldırıldı.');
    }

    /**
     * Kanalın açık YouTube sayfalarından videolarını otomatik tarayıp çeker.
     */
    public function syncChannelVideos(Request $request): RedirectResponse
    {
        $user = $request->user();
        $channel = $user->activeChannelLink?->channel;

        if (!$channel) {
            return back()->withErrors(['error' => 'Bağlı bir kanalınız bulunamadı.']);
        }

        $targetUrl = $channel->custom_url
            ? 'https://www.youtube.com/' . ltrim($channel->custom_url, '/') . '/videos'
            : 'https://www.youtube.com/channel/' . $channel->youtube_channel_id . '/videos';

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $html = curl_exec($ch);

        $videoIds = [];
        if ($html) {
            preg_match_all('/"videoId":"([a-zA-Z0-9_-]{11})"/', $html, $matches);
            // Shorts video ID'lerini tespit edip listeden çıkar
            preg_match_all('#/shorts/([a-zA-Z0-9_-]{11})#', $html, $shortsMatches);
            $shortsIds = array_flip($shortsMatches[1] ?? []);
            if (!empty($matches[1])) {
                $videoIds = array_values(array_filter(
                    array_unique($matches[1]),
                    fn($id) => !isset($shortsIds[$id])
                ));
            }
        }

        $added = 0;
        foreach (array_slice($videoIds, 0, 12) as $vid) {
            $existing = Video::where('youtube_video_id', $vid)->first();
            if ($existing) {
                continue;
            }

            // OEmbed ile başlık ve kapak görselini çek
            $oembedUrl = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $vid . '&format=json';
            $chOe = curl_init($oembedUrl);
            curl_setopt_array($chOe, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $oembedRaw = curl_exec($chOe);
            $meta = $oembedRaw ? json_decode($oembedRaw, true) : null;

            Video::create([
                'youtube_video_id'   => $vid,
                'youtube_channel_id' => $channel->id,
                'title'              => $meta['title'] ?? ('Video ' . $vid),
                'thumbnail_url'      => $meta['thumbnail_url'] ?? ('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg'),
                'published_at'       => now(),
                'availability'       => 'available',
                'last_checked_at'    => now(),
            ]);
            $added++;
        }

        if ($added > 0) {
            return redirect()->route('videos.showcase')
                ->with('status', $added . ' adet video başarıyla senkronize edildi! Şimdi dilediğiniz videoları vitrininize seçebilirsiniz.');
        }

        return redirect()->route('videos.showcase')
            ->withErrors(['error' => 'Kanalınızdan otomatik video bulunamadı. Aşağıdaki formdan video linkinizi doğrudan yapıştırarak anında ekleyebilirsiniz.']);
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

        // oEmbed yazar adı ile kanal başlığını karşılaştır (sahiplik doğrulama)
        $authorName = strtolower(trim($oembedData['author_name'] ?? ''));
        $channelTitle = strtolower(trim($channel->title ?? ''));
        if ($authorName && $channelTitle && $authorName !== $channelTitle) {
            return back()->withErrors(['video_url' => 'Bu video kanalınıza ait görünmüyor. Yalnızca kendi kanalınızdaki videolar eklenebilir.'])->withInput();
        }

        $title = $oembedData['title'] ?? ('Video ' . $videoId);
        $thumb = $oembedData['thumbnail_url'] ?? ('https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg');

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

        $currentCount = UserVideoSelection::where('user_id', $user->id)->count();
        if ($currentCount >= 3) {
            return redirect()->route('videos.showcase')
                ->with('status', 'Video kütüphanenize eklendi. Vitrininiz dolu (3/3), önce bir video çıkarın.');
        }

        UserVideoSelection::firstOrCreate([
            'user_id'  => $user->id,
            'video_id' => $video->id,
        ]);

        return redirect()->route('videos.showcase')
            ->with('status', 'Video başarıyla eklendi ve vitrininize kaydedildi!');
    }
}
