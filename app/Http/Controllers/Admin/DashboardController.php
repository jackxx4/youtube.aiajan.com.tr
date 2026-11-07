<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\PlatformFollow;
use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use App\Services\YouTubeChannelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $totalAdmins = Admin::count();
        $activeSupporters = DB::table('supporter_statuses')
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->count();
        $totalMessages = DB::table('audit_logs')
            ->where('action', 'contact_form_submitted')
            ->count();

        // 2. Özet Hareketler: Son 5 Vitrin Videosu & Son 5 Takip
        $recentShowcaseVideos = UserVideoSelection::with(['user', 'video.channel'])
            ->latest('id')
            ->limit(5)
            ->get();

        $recentFollows = PlatformFollow::with(['follower', 'followed', 'channel'])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'usersToday',
            'totalChannels',
            'totalShowcaseVideos',
            'totalVideos',
            'totalFollows',
            'followsToday',
            'totalAdmins',
            'totalMessages',
            'recentShowcaseVideos',
            'recentFollows'
        ));
    }

    /**
     * Başlıklara Göre Platform Takipleri (Abone/Takip Havuzu)
     */
    public function follows(Request $request): View
    {
        $pool = $request->input('pool'); // normal, supporter
        $search = trim((string) $request->input('search', ''));

        $query = PlatformFollow::with(['follower', 'followed', 'channel'])->latest('id');

        if (in_array($pool, ['normal', 'supporter'], true)) {
            $query->where('pool_type', $pool);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('follower', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('followed', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('channel', function ($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                       ->orWhere('youtube_channel_id', 'like', "%{$search}%");
                });
            });
        }

        $follows = $query->paginate(25)->withQueryString();
        $totalFollows = PlatformFollow::count();
        $normalCount = PlatformFollow::where('pool_type', 'normal')->count();
        $supporterCount = PlatformFollow::where('pool_type', 'supporter')->count();

        return view('admin.follows', compact('follows', 'pool', 'search', 'totalFollows', 'normalCount', 'supporterCount'));
    }

    /**
     * Takip kaydını siler.
     */
    public function destroyFollow(Request $request, PlatformFollow $follow): RedirectResponse
    {
        $follow->delete();
        return back()->with('status', 'Takip kaydı başarıyla silindi.');
    }

    /**
     * İletişim & Destek Talepleri Müstakil Sayfası
     */
    public function messages(): View
    {
        $messages = DB::table('audit_logs')
            ->where('action', 'contact_form_submitted')
            ->latest('id')
            ->paginate(20);

        $parsedMessages = $messages->through(function ($row) {
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

        $totalMessages = DB::table('audit_logs')
            ->where('action', 'contact_form_submitted')
            ->count();

        return view('admin.messages', compact('parsedMessages', 'totalMessages'));
    }

    /**
     * Ayrı Kullanıcı Yönetimi Sayfası (Normal İçerik Üreticiler)
     * Üyelerden admin seçimi kaldırıldı; herkes standart üreticidir.
     */
    public function users(Request $request, YouTubeChannelService $channelsService): View
    {
        // Otomatik Mantıksal Onarım (Self-Healing):
        // 1. Bağlı kanalı henüz çözülmemiş olan kullanıcıların (veya @pages.plusgoogle.com marka hesaplarının) YouTube kanalını otomatik bağla
        $unlinkedUsers = User::whereDoesntHave('channelLinks')->limit(5)->get();
        foreach ($unlinkedUsers as $u) {
            try {
                $resolved = $channelsService->resolveBestChannelByName($u->name, $u->email, $u->avatar_url);
                if ($resolved && !empty($resolved['youtube_channel_id'])) {
                    $ch = YouTubeChannel::updateOrCreate(
                        ['youtube_channel_id' => $resolved['youtube_channel_id']],
                        collect($resolved)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                    );
                    try {
                        $channelsService->syncChannelVideos($ch);
                    } catch (\Throwable) {}

                    UserChannelLink::firstOrCreate(
                        ['user_id' => $u->id, 'youtube_channel_id' => $ch->id],
                        ['started_at' => now(), 'ended_at' => null]
                    );
                    $gids = array_values(array_unique(array_filter(array_merge((array) ($u->google_channel_ids ?? []), [$ch->youtube_channel_id]))));
                    $u->update(['google_channel_ids' => $gids]);
                }
            } catch (\Throwable) {}
        }

        // 2. Eğer sistemde gerçek e-postalı (@gmail.com vb.) ana üye varsa, @pages.plusgoogle.com marka hesabı satırlarını ana üyeye birleştir
        $primaryUser = User::where('email', 'not like', '%@pages.plusgoogle.com')->where('status', 'active')->latest('id')->first();
        if ($primaryUser) {
            $brandUsers = User::where('email', 'like', '%@pages.plusgoogle.com')->where('id', '<>', $primaryUser->id)->get();
            foreach ($brandUsers as $bu) {
                try {
                    $gids = array_values(array_unique(array_filter(array_merge((array) ($primaryUser->google_channel_ids ?? []), (array) ($bu->google_channel_ids ?? [])))));
                    $primaryUser->update(['google_channel_ids' => $gids]);
                    UserChannelLink::where('user_id', $bu->id)->update(['user_id' => $primaryUser->id, 'ended_at' => now()]);
                    UserVideoSelection::where('user_id', $bu->id)->update(['user_id' => $primaryUser->id]);
                    DB::table('youtube_oauth_tokens')->where('user_id', $bu->id)->delete();
                    $bu->delete();
                } catch (\Throwable) {}
            }
        }

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');

        $query = User::with(['activeChannelLink.channel', 'channelLinks.channel'])->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'suspended'], true)) {
            $query->where('status', $status);
        }

        $users = $query->paginate(20)->withQueryString();
        $totalUsers = User::count();
        $totalChannels = YouTubeChannel::count();

        return view('admin.users', compact('users', 'search', 'status', 'totalUsers', 'totalChannels'));
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
     * Vitrindeki Videolar Listesi (Kullanıcıların eklediği tüm videolar)
     */
    public function showcaseVideos(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $query = UserVideoSelection::with(['user', 'video.channel'])->latest('id');

        if ($search !== '') {
            $query->whereHas('video', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('youtube_video_id', 'like', "%{$search}%");
            })->orWhereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $selections = $query->paginate(24)->withQueryString();
        $totalShowcaseVideos = UserVideoSelection::count();

        return view('admin.showcase_videos', compact('selections', 'search', 'totalShowcaseVideos'));
    }

    /**
     * Seçilen videoyu vitrinden kaldırır.
     */
    public function destroyShowcaseVideo(Request $request, UserVideoSelection $selection): RedirectResponse
    {
        $videoTitle = $selection->video?->title ?: 'Video';
        $selection->delete();

        return back()->with('status', "“{$videoTitle}” vitrinden başarıyla kaldırıldı.");
    }

    /**
     * Müstakil Yöneticiler Yönetimi Sayfası (admins tablosu)
     */
    public function admins(): View
    {
        $admins = Admin::latest('id')->get();
        $currentAdmin = Auth::guard('admin')->user();

        return view('admin.admins', compact('admins', 'currentAdmin'));
    }

    /**
     * Yeni Yönetici Ekle
     */
    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:admins,username'],
            'email'    => ['required', 'email', 'max:150', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', 'in:super_admin,admin'],
        ], [
            'username.unique' => 'Bu kullanıcı adı zaten başka bir yöneticiye ait.',
            'email.unique'    => 'Bu e-posta adresi zaten kullanımda.',
            'password.min'    => 'Yönetici parolası en az 6 karakter olmalıdır.',
        ]);

        Admin::create([
            'name'       => $validated['name'],
            'username'   => strtolower($validated['username']),
            'email'      => strtolower($validated['email']),
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'is_active'  => true,
        ]);

        return back()->with('status', "“{$validated['name']}” adlı yeni yönetici hesabı başarıyla oluşturuldu.");
    }

    /**
     * Yönetici Hesabını Sil
     */
    public function destroyAdmin(Request $request, Admin $admin): RedirectResponse
    {
        $currentAdmin = Auth::guard('admin')->user();
        if ($currentAdmin && $currentAdmin->id === $admin->id) {
            return back()->withErrors(['admin' => 'Kendi yönetici hesabınızı silemezsiniz.']);
        }

        $name = $admin->name;
        $admin->delete();

        return back()->with('status', "“{$name}” adlı yönetici hesabı sistemden silindi.");
    }

    /**
     * Yönetici Durumunu Değiştir (Aktif / Pasif)
     */
    public function toggleAdminStatus(Request $request, Admin $admin): RedirectResponse
    {
        $currentAdmin = Auth::guard('admin')->user();
        if ($currentAdmin && $currentAdmin->id === $admin->id) {
            return back()->withErrors(['admin' => 'Kendi yönetici hesabınızın durumunu değiştiremezsiniz.']);
        }

        $newStatus = !$admin->is_active;
        $admin->update(['is_active' => $newStatus]);
        $statusText = $newStatus ? 'aktif hale getirildi' : 'pasife alındı';

        return back()->with('status', "“{$admin->name}” yöneticisi {$statusText}.");
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
     * Kullanıcı rolünü değiştirir (Geriye dönük uyumluluk için korundu).
     */
    public function toggleRole(Request $request, User $user): RedirectResponse
    {
        return back()->withErrors(['admin' => 'Üyelerden yönetici atama özelliği güvenlik nedeniyle kaldırılmıştır. Lütfen yöneticileri Yöneticiler sayfasından yönetin.']);
    }

    /**
     * Kullanıcı durumunu değiştirir (active <-> suspended).
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
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
            return back()->withErrors(['admin' => 'Kendi hesabınızı silemezsiniz.']);
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
            $videoIds = Video::where('youtube_channel_id', $channel->id)->pluck('id');

            if ($videoIds->isNotEmpty()) {
                UserVideoSelection::whereIn('video_id', $videoIds)->delete();
            }

            Video::where('youtube_channel_id', $channel->id)->delete();
            PlatformFollow::where('youtube_channel_id', $channel->id)->delete();
            DB::table('user_channel_links')->where('youtube_channel_id', $channel->id)->delete();

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

