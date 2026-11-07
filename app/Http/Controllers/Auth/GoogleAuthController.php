<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\YouTubeChannel;
use App\Models\YouTubeOAuthToken;
use App\Services\YouTubeChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Socialite;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(YouTubeChannelService $channels): RedirectResponse
    {
        try {
            $channels->assertConfigured();
            return Socialite::driver('google')
                ->scopes(['openid', 'email', 'profile', 'https://www.googleapis.com/auth/youtube.readonly'])
                ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
                ->redirect();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login', ['auto' => 'google']);
        }
    }

    public function callback(YouTubeChannelService $channels): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
            $available = $channels->listMine($google->token);

            $user = DB::transaction(function () use ($google, $available) {
                $user = User::firstOrNew(['google_sub' => $google->getId()]);
                if ($user->exists && $user->status !== 'active') {
                    return $user;
                }
                if (!$user->exists) {
                    $user->username = $this->uniqueUsername($google->getNickname() ?: $google->getName() ?: 'creator');
                    $user->status = 'active';
                }
                $user->name = $google->getName() ?: 'YouTube Kullanıcısı';
                $user->email = $google->getEmail();
                $user->avatar_url = $google->getAvatar();
                $user->save();

                if ($available->isNotEmpty()) {
                    foreach ($available as $data) {
                        YouTubeChannel::updateOrCreate(
                            ['youtube_channel_id' => $data['youtube_channel_id']],
                            collect($data)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                        );
                    }
                }

                $storedToken = YouTubeOAuthToken::firstOrNew(['user_id' => $user->id]);
                $storedToken->access_token_enc = Crypt::encryptString($google->token);
                if ($google->refreshToken) {
                    $storedToken->refresh_token_enc = Crypt::encryptString($google->refreshToken);
                }
                $storedToken->expires_at = $google->expiresIn ? now()->addSeconds($google->expiresIn) : null;
                $storedToken->scopes = 'openid email profile https://www.googleapis.com/auth/youtube.readonly';
                $storedToken->save();

                return $user;
            });

            if ($user->status !== 'active') {
                return redirect()->route('login')->withErrors(['google' => 'Bu platform hesabı şu anda kullanıma açık değil.']);
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            if ($available->isNotEmpty()) {
                session(['available_youtube_channels' => $available->pluck('youtube_channel_id')->all()]);
            }

            $active = $user->activeChannelLink()->first();
            return ($active && $active->started_at && $active->started_at->gt(now()->subDays(30)))
                ? redirect()->route('dashboard')
                : redirect()->route('channels.select');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('login')->withErrors(['google' => 'Google girişi tamamlanamadı. OAuth ayarlarını ve YouTube kanal erişimini kontrol edin.']);
        }
    }

    /**
     * aiajan.com.tr Firebase Google Auth Köprüsü
     * Client-side Google oturumu açtıktan sonra Firebase idToken ve YouTube accessToken ile login olur.
     */
    public function firebaseCallback(Request $request, YouTubeChannelService $channels): JsonResponse
    {
        $idToken = (string) $request->input('idToken', '');
        $accessToken = (string) $request->input('accessToken', '');

        if (!$idToken) {
            return response()->json(['success' => false, 'message' => 'Yetkilendirme belirteci (token) eksik.'], 422);
        }

        $firebaseUser = $this->verifyFirebaseToken($idToken);
        if (!$firebaseUser) {
            return response()->json(['success' => false, 'message' => 'Google yetkilendirmesi doğrulanamadı.'], 401);
        }

        $googleSub = $firebaseUser['uid'];
        if (!$googleSub) {
            return response()->json(['success' => false, 'message' => 'Geçersiz kimlik bilgisi.'], 401);
        }
        $email = $firebaseUser['email'];
        $name = $firebaseUser['name'] ?: ($firebaseUser['email'] ? explode('@', $firebaseUser['email'])[0] : 'YouTube Üreticisi');
        $avatar = $firebaseUser['photo'] ?? null;

        $available = collect();
        if ($accessToken) {
            try {
                $available = $channels->listMine($accessToken);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            $user = DB::transaction(function () use ($googleSub, $name, $email, $avatar, $accessToken, $available) {
                $user = User::firstOrNew(['google_sub' => $googleSub]);
                if ($user->exists && $user->status !== 'active') {
                    return $user;
                }
                if (!$user->exists) {
                    $user->username = $this->uniqueUsername($name);
                    $user->status = 'active';
                }
                $user->name = $name;
                $user->email = $email;
                if ($avatar) {
                    $user->avatar_url = $avatar;
                }
                $user->save();

                if ($available->isNotEmpty()) {
                    foreach ($available as $data) {
                        YouTubeChannel::updateOrCreate(
                            ['youtube_channel_id' => $data['youtube_channel_id']],
                            collect($data)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
                        );
                    }
                }

                if ($accessToken) {
                    $storedToken = YouTubeOAuthToken::firstOrNew(['user_id' => $user->id]);
                    $storedToken->access_token_enc = Crypt::encryptString($accessToken);
                    $storedToken->scopes = 'openid email profile https://www.googleapis.com/auth/youtube.readonly';
                    $storedToken->expires_at = now()->addHour();
                    $storedToken->save();
                }

                return $user;
            });

            if ($user->status !== 'active') {
                return response()->json(['success' => false, 'message' => 'Bu hesap şu anda kullanıma kapalı.'], 403);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            if ($available->isNotEmpty()) {
                session(['available_youtube_channels' => $available->pluck('youtube_channel_id')->all()]);
            }

            $active = $user->activeChannelLink()->first();
            $redirectUrl = ($active && $active->started_at && $active->started_at->gt(now()->subDays(30)))
                ? route('dashboard')
                : route('channels.select');

            return response()->json([
                'success' => true,
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Giriş işlemi sırasında hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    private function verifyFirebaseToken(string $idToken): ?array
    {
        $apiKey = config('services.firebase.api_key') ?: env('FIREBASE_API_KEY');
        if (empty($apiKey) || empty($idToken)) {
            return null;
        }

        $ch = curl_init('https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . urlencode($apiKey));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['idToken' => $idToken]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$body) {
            return null;
        }

        $data = json_decode((string) $body, true);
        $user = $data['users'][0] ?? null;
        if (!$user) {
            return null;
        }

        return [
            'uid' => (string) ($user['localId'] ?? ''),
            'email' => strtolower((string) ($user['email'] ?? '')),
            'name' => (string) ($user['displayName'] ?? ''),
            'photo' => (string) ($user['photoUrl'] ?? ''),
        ];
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('home');
    }

    private function uniqueUsername(string $name): string
    {
        $base = substr(Str::slug($name, '_') ?: 'creator', 0, 40);
        $candidate = $base;
        $i = 1;
        while (User::where('username', $candidate)->exists()) {
            $suffix = (string) $i++;
            $candidate = substr($base, 0, 50 - strlen($suffix)) . $suffix;
        }
        return $candidate;
    }
}
