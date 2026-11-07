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

        // 2. Son Kullanıcılar (15 adet)
        $recentUsers = User::with('activeChannelLink.channel')
            ->latest('id')
            ->limit(15)
            ->get();

        // 3. Son YouTube Kanalları (10 adet)
        $recentChannels = YouTubeChannel::withCount('videos')
            ->latest('last_synced_at')
            ->limit(10)
            ->get();

        // 4. Son İletişim & Destek Talepleri (audit_logs üzerinden)
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

        // 5. Sistem & Telemetri
        $systemInfo = [
            'version'     => config('version.current', '1.4.9'),
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
            'recentUsers',
            'recentChannels',
            'recentMessages',
            'systemInfo'
        ));
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
     * Sistem önbelleğini güvenli şekilde temizler.
     */
    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');
        return back()->with('status', 'Sistem önbellekleri (config, route, view) başarıyla temizlendi.');
    }
}
