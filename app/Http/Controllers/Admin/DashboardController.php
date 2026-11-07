<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformFollow;
use App\Models\User;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Temel İstatistikler
        $totalUsers = User::count();
        $usersToday = User::whereDate('created_at', today())->count();
        $totalChannels = YouTubeChannel::count();
        $totalShowcaseVideos = UserVideoSelection::count();
        $totalVideos = Video::count();
        $totalFollows = PlatformFollow::count();
        $followsToday = PlatformFollow::whereDate('created_at', today())->count();
        $activeSupporters = DB::table('supporter_statuses')
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->count();
        $totalMessages = DB::table('audit_logs')
            ->where('action', 'contact_form_submitted')
            ->count();

        // 2. Son Eklenen YouTube Kanalları (10 adet)
        $recentChannels = YouTubeChannel::withCount('videos')
            ->latest('last_synced_at')
            ->limit(10)
            ->get();

        // 3. Son İletişim & Destek Talepleri (audit_logs üzerinden)
        $recentMessages = DB::table('audit_logs')
            ->where('action', 'contact_form_submitted')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $meta = json_decode((string) $row->metadata, true) ?: [];
                return [
                    'id'         => $row->id,
                    'name'       => $meta['name'] ?? 'İsimsiz',
                    'email'      => $meta['email'] ?? '—',
                    'subject'    => $meta['subject'] ?? 'Genel',
                    'message'    => $meta['message'] ?? '',
                    'ip_hash'    => substr((string) ($row->ip_hash ?? ''), 0, 10) . '...',
                    'created_at' => $row->created_at,
                ];
            });

        // 4. Sistem & Telemetri
        $systemInfo = [
            'version'     => config('version.current', '1.5.9'),
            'release'     => config('version.release_name', 'YouTube Keşif'),
            'php_version' => PHP_VERSION,
            'laravel_ver' => app()->version(),
            'environment' => app()->environment(),
            'db_driver'   => DB::connection()->getDriverName(),
        ];

        return view('admin.dashboard', compact(
            'totalUsers',
            'usersToday',
            'totalChannels',
            'totalShowcaseVideos',
            'totalVideos',
            'totalFollows',
            'followsToday',
            'activeSupporters',
            'totalMessages',
            'recentChannels',
            'recentMessages',
            'systemInfo'
        ));
    }

    /**
     * Ayrı Kullanıcı Yönetimi Sayfası
     */
    public function users(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $role = $request->input('role');
        $status = $request->input('status');

        $query = User::with('activeChannelLink.channel')->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($role, ['admin', 'user'], true)) {
            $query->where('role', $role);
        }

        if (in_array($status, ['active', 'suspended'], true)) {
            $query->where('status', $status);
        }

        $users = $query->paginate(20)->withQueryString();
        $totalUsers = User::count();
        $totalChannels = YouTubeChannel::count();

        return view('admin.users', compact('users', 'search', 'role', 'status', 'totalUsers', 'totalChannels'));
    }

    /**
     * Müstakil YouTube Kanal Yönetimi Sayfası
     */
    public function channels(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $query = YouTubeChannel::withCount('videos')
            ->with(['links.user'])
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('youtube_channel_id', 'like', "%{$search}%")
                  ->orWhere('custom_url', 'like', "%{$search}%");
            });
        }

        $channels = $query->paginate(20)->withQueryString();
        $totalChannels = YouTubeChannel::count();
        $orphanCount = YouTubeChannel::whereDoesntHave('links')->count();

        return view('admin.channels', compact('channels', 'search', 'totalChannels', 'orphanCount'));
    }

    /**
     * Hiçbir kullanıcıya bağlı olmayan sahipsiz kanalları topluca siler.
     */
    public function cleanOrphanChannels(): RedirectResponse
    {
        $orphans = YouTubeChannel::whereDoesntHave('links')->get();
        $count = $orphans->count();

        if ($count === 0) {
            return back()->with('status', 'Temizlenecek sahipsiz kanal bulunamadı.');
        }

        DB::transaction(function () use ($orphans) {
            foreach ($orphans as $channel) {
                $videoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');
                if ($videoIds->isNotEmpty()) {
                    UserVideoSelection::whereIn('video_id', $videoIds)->delete();
                }
                Video::where('youtube_channel_id', $channel->id)->delete();
                PlatformFollow::where('youtube_channel_id', $channel->id)->delete();
                $channel->delete();
            }
        });

        return back()->with('status', "Toplam {$count} adet sahipsiz YouTube kanalı ve bunlara ait tüm veriler başarıyla temizlendi.");
    }

    /**
     * Kullanıcı rolünü değiştirir (admin <-> user).
     */
    public function toggleRole(Request $request, User $user): RedirectResponse
    {
        $currentAdmin = $request->user();
        if ($currentAdmin->id === $user->id) {
            return back()->withErrors(['admin' => 'Kendi yönetici rolünüzü değiştiremezsiniz.']);
        }

        $newRole = $user->role === 'admin' ? 'user' : 'admin';
        $user->update(['role' => $newRole]);

        return back()->with('status', "{$user->name} kullanıcısının rolü '{$newRole}' olarak güncellendi.");
    }

    /**
     * Kullanıcı durumunu değiştirir (active <-> suspended).
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $currentAdmin = $request->user();
        if ($currentAdmin->id === $user->id) {
            return back()->withErrors(['admin' => 'Kendi hesap durumunuzu askıya alamazsınız.']);
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('status', "{$user->name} kullanıcısının durumu '{$newStatus}' olarak güncellendi.");
    }

    /**
     * Üyeyi ve ilişkili tüm verilerini kalıcı olarak siler.
     */
    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $currentAdmin = $request->user('admin') ?? $request->user();
        if ($currentAdmin && $currentAdmin instanceof User && $currentAdmin->id === $user->id) {
            return back()->withErrors(['admin' => 'Kendi yönetici hesabınızı silemezsiniz.']);
        }

        $userName = $user->name;

        DB::transaction(function () use ($user) {
            DB::table('user_video_selections')->where('user_id', $user->id)->delete();
            DB::table('platform_follows')->where('follower_user_id', $user->id)->orWhere('followed_user_id', $user->id)->delete();
            DB::table('user_channel_links')->where('user_id', $user->id)->delete();
            DB::table('youtube_oauth_tokens')->where('user_id', $user->id)->delete();
            DB::table('supporter_statuses')->where('user_id', $user->id)->delete();
            DB::table('user_impressions')->where('viewer_user_id', $user->id)->orWhere('shown_user_id', $user->id)->delete();
            DB::table('user_blocks')->where('blocker_user_id', $user->id)->orWhere('blocked_user_id', $user->id)->delete();
            $user->delete();
        });

        return back()->with('status', "“{$userName}” kullanıcısı ve platformdaki tüm bağlı verileri başarıyla silindi.");
    }

    /**
     * Bağlı YouTube kanalını ve tüm video, vitrin ve takip verilerini kalıcı olarak siler.
     */
    public function destroyChannel(Request $request, YouTubeChannel $channel): RedirectResponse
    {
        $channelTitle = $channel->title ?: $channel->youtube_channel_id;
        $googleChannelId = $channel->youtube_channel_id;

        DB::transaction(function () use ($channel, $googleChannelId) {
            // 1. Bu kanala ait videoların ID listesini al
            $videoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');

            // 2. Vitrindeki video seçimlerini temizle
            if ($videoIds->isNotEmpty()) {
                UserVideoSelection::whereIn('video_id', $videoIds)->delete();
            }

            // 3. Bu kanala ait videoları sil
            Video::where('youtube_channel_id', $channel->id)->delete();

            // 4. Bu kanala yapılan platform takiplerini sil
            PlatformFollow::where('youtube_channel_id', $channel->id)->delete();

            // 5. Kullanıcı-kanal eşleşmelerini sil
            DB::table('user_channel_links')->where('youtube_channel_id', $channel->id)->delete();

            // 6. Kullanıcıların google_channel_ids JSON dizisinden bu kanalı kaldır
            if (!empty($googleChannelId)) {
                $affectedUsers = User::whereNotNull('google_channel_ids')->get();
                foreach ($affectedUsers as $u) {
                    $ids = $u->google_channel_ids;
                    if (is_array($ids) && in_array($googleChannelId, $ids, true)) {
                        $newIds = array_values(array_filter($ids, fn($id) => $id !== $googleChannelId));
                        $u->update(['google_channel_ids' => $newIds]);
                    }
                }
            }

            // 7. Kanal kaydını sil
            $channel->delete();
        });

        return back()->with('status', "“{$channelTitle}” kanalı ve ilişkili tüm videolar platformdan başarıyla silindi.");
    }

    /**
     * Sistem önbelleğini güvenli şekilde temizler.
     */
    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');
        return back()->with('status', 'Sistem önbellekleri (config, route, view) başarıyla temizlendi.');
    }
}
