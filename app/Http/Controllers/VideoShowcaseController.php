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
            ->with('status', count($ids) . ' video vitrininize kaydedildi.');
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
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $html = curl_exec($ch);

        $videoIds = [];
        if ($html) {
            preg_match_all('/"videoId":"([a-zA-Z0-9_-]{11})"/', $html, $matches);
            if (!empty($matches[1])) {
                $videoIds = array_unique($matches[1]);
            }
        }

        $added = 0;
        foreach (array_slice($videoIds, 0, 12) as $vid) {
            $existing = Video::where('youtube_video_id', $vid)->first();
            if ($existing) {
                $added++;
                continue;
            }

            // OEmbed ile başlık ve kapak görselini çek
            $oembedUrl = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $vid . '&format=json';
            $chOe = curl_init($oembedUrl);
            curl_setopt_array($chOe, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => false,
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

        $videoId = null;
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/^[\w-]{11}$/', $url)) {
            $videoId = $url;
        }

        if (!$videoId) {
            return back()->withErrors(['video_url' => 'Geçersiz YouTube video bağlantısı. Örn: https://www.youtube.com/watch?v=...'])->withInput();
        }

        $oembedUrl = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $videoId . '&format=json';
        $ch = curl_init($oembedUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $oembedRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $title = 'Video ' . $videoId;
        $thumb = 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg';

        if ($oembedRaw && $httpCode === 200) {
            $oembedData = json_decode($oembedRaw, true);
            if (!empty($oembedData['title'])) {
                $title = $oembedData['title'];
            }
            if (!empty($oembedData['thumbnail_url'])) {
                $thumb = $oembedData['thumbnail_url'];
            }
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

        $currentCount = UserVideoSelection::where('user_id', $user->id)->count();
        if ($currentCount < 3) {
            UserVideoSelection::firstOrCreate([
                'user_id'  => $user->id,
                'video_id' => $video->id,
            ]);
        }

        return redirect()->route('videos.showcase')
            ->with('status', 'Video başarıyla eklendi ve vitrininize kaydedildi!');
    }
}
