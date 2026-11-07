<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\UserVideoSelection;
use App\Models\YouTubeChannel;
use App\Models\YouTubeOAuthToken;
use App\Services\YouTubeChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
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

            $user = DB::transaction(function () use ($google) {
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

            $redirectUrl = $this->processChannelsAndRedirect($user, $available, $channels);
            return redirect($redirectUrl);
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
            $user = DB::transaction(function () use ($googleSub, $name, $email, $avatar, $accessToken) {
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

            $redirectUrl = $this->processChannelsAndRedirect($user, $available, $channels);

            return response()->json([
                'success' => true,
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Giriş işlemi sırasında hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Kullanıcının Google hesabından çekilen tüm kanalları sisteme kaydeder,
     * her bir kanalın son videolarını otomatik çeker,
     * ilk kanalı aktif kanal olarak bağlar ve diğer kanalları saklar.
     */
    private function processChannelsAndRedirect(User $user, Collection $available, YouTubeChannelService $channelsService): string
    {
        if ($available->isEmpty()) {
            return $user->activeChannelLink()->exists()
                ? route('dashboard')
                : route('channels.select');
        }

        $allChannelIds = [];
        $firstChannel = null;

        foreach ($available as $data) {
            $ch = YouTubeChannel::updateOrCreate(
                ['youtube_channel_id' => $data['youtube_channel_id']],
                collect($data)->except(['subscriber_count', 'view_count'])->all() + ['last_synced_at' => now()]
            );

            $allChannelIds[] = $ch->youtube_channel_id;

            // Bu kanalın son videolarını otomatik çek (böylece kullanıcı vitrin videolarını hemen seçebilir)
            try {
                $channelsService->syncChannelVideos($ch);
            } catch (\Throwable $e) {
                Log::warning("Girişte kanal videoları çekilemedi: {$ch->youtube_channel_id}", ['err' => $e->getMessage()]);
            }

            if (!$firstChannel) {
                $firstChannel = $ch;
            }
        }

        // Kullanıcının kayıtlı tüm Google kanal ID'lerini güncelle
        $existingStored = (array) ($user->google_channel_ids ?? []);
        $mergedIds = array_values(array_unique(array_filter(array_merge($existingStored, $allChannelIds))));
        $user->update(['google_channel_ids' => $mergedIds]);
        session(['available_youtube_channels' => $mergedIds]);

        // Aktif kanal kontrolü: Kullanıcının henüz aktif bir kanalı yoksa ilk kanalı aktif yap
        $hasActive = $user->activeChannelLink()->exists();
        if (!$hasActive && $firstChannel) {
            $isLinkedToOther = UserChannelLink::where('youtube_channel_id', $firstChannel->id)
                ->whereNull('ended_at')
                ->where('user_id', '<>', $user->id)
                ->exists();

            if (!$isLinkedToOther) {
                UserChannelLink::create([
                    'user_id'            => $user->id,
                    'youtube_channel_id' => $firstChannel->id,
                    'started_at'         => now(),
                ]);
            }
        }

        // Kullanıcının diğer kanallarını da UserChannelLink geçmişine ekle (ended_at = now())
        // Böylece diğer kanalların da bu kullanıcıya ait olduğu DB düzeyinde bilinir
        foreach ($available as $data) {
            $ch = YouTubeChannel::where('youtube_channel_id', $data['youtube_channel_id'])->first();
            if ($ch && !UserChannelLink::where('user_id', $user->id)->where('youtube_channel_id', $ch->id)->exists()) {
                UserChannelLink::create([
                    'user_id'            => $user->id,
                    'youtube_channel_id' => $ch->id,
                    'started_at'         => now(),
                    'ended_at'           => now(),
                ]);
            }
        }

        $totalChannelsCount = count($mergedIds);

        if ($totalChannelsCount > 1) {
            session()->flash('status', "Google hesabınıza bağlı {$totalChannelsCount} YouTube kanalı başarıyla aktarıldı. Yönetmek istediğiniz kanalı seçerek vitrin videolarını belirleyebilirsiniz.");
            return route('channels.select');
        }

        // Tek kanal varsa vitrin kontrolü yap
        $activeLink = $user->activeChannelLink()->first();
        if ($activeLink) {
            $showcaseCount = UserVideoSelection::where('user_id', $user->id)
                ->where('youtube_channel_id', $activeLink->youtube_channel_id)
                ->count();

            if ($showcaseCount < 3) {
                session()->flash('status', "“{$activeLink->channel->title}” kanalınız bağlandı! Keşif havuzunda yer alması için lütfen 3 vitrin videosu seçin.");
                return route('videos.showcase');
            }
        }

        return route('dashboard');
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
