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
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Google Giriş Yönlendirmesi (Firebase OAuth Köprüsü üzerinden otomatik başlatır)
     */
    public function redirect(YouTubeChannelService $channels): RedirectResponse
    {
        return redirect()->route('login', ['auto' => 'google']);
    }

    /**
     * Resmi Google OAuth 2.0 Geri Dönüşü (Authorization Code -> Access Token & User Info)
     */
    public function callback(Request $request, YouTubeChannelService $channels): RedirectResponse
    {
        $code = (string) $request->input('code');
        if (empty($code)) {
            return redirect()->route('login')->withErrors(['google' => 'Google oturum açma işlemi iptal edildi veya yetki kodu alınamadı.']);
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');

        // 1. Token değişimi (code -> access_token & refresh_token)
        $tokenData = $this->exchangeGoogleCode($code, $clientId, $clientSecret, $redirectUri);
        if (!$tokenData || empty($tokenData['access_token'])) {
            return redirect()->route('login')->withErrors(['google' => 'Google erişim belirteci doğrulanamadı. Lütfen tekrar deneyin.']);
        }

        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = $tokenData['expires_in'] ?? 3600;

        // 2. Kullanıcı bilgilerini Google Userinfo API'den çek
        $googleUser = $this->getGoogleUserInfo($accessToken);
        if (!$googleUser || empty($googleUser['sub'])) {
            return redirect()->route('login')->withErrors(['google' => 'Google kullanıcı profili alınamadı.']);
        }

        $googleSub = $googleUser['sub'];
        $email = strtolower((string) ($googleUser['email'] ?? ''));
        $name = (string) ($googleUser['name'] ?: ($email ? explode('@', $email)[0] : 'YouTube Üreticisi'));
        $avatar = $googleUser['picture'] ?? null;

        // 3. Kullanıcının yetkili tüm YouTube kanallarını doğrudan çek
        $available = $channels->listMine($accessToken);

        // 4. Veritabanı işlemleri ve oturum açma
        try {
            $user = DB::transaction(function () use ($googleSub, $name, $email, $avatar, $accessToken, $refreshToken, $expiresIn) {
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

                $storedToken = YouTubeOAuthToken::firstOrNew(['user_id' => $user->id]);
                $storedToken->access_token_enc = Crypt::encryptString($accessToken);
                if ($refreshToken) {
                    $storedToken->refresh_token_enc = Crypt::encryptString($refreshToken);
                }
                $storedToken->expires_at = now()->addSeconds($expiresIn);
                $storedToken->scopes = 'openid email profile https://www.googleapis.com/auth/youtube.readonly';
                $storedToken->save();

                return $user;
            });

            if ($user->status !== 'active') {
                return redirect()->route('login')->withErrors(['google' => 'Bu platform hesabı şu anda kullanıma açık değil.']);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            $redirectUrl = $this->processChannelsAndRedirect($user, $available, $channels);
            return redirect($redirectUrl);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->withErrors(['google' => 'Giriş işlemi tamamlanırken hata oluştu: ' . $e->getMessage()]);
        }
    }

    /**
     * Google OAuth Code -> Token takası
     */
    private function exchangeGoogleCode(string $code, string $clientId, string $clientSecret, string $redirectUri): ?array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            Log::warning('Google OAuth token değişimi başarısız', ['code' => $httpCode, 'resp' => mb_substr((string) $response, 0, 500)]);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Google Access Token ile profil bilgisi çekme
     */
    private function getGoogleUserInfo(string $accessToken): ?array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        return json_decode($response, true);
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
        $isBrandAccount = str_ends_with($email, '@pages.plusgoogle.com');

        $available = collect();
        if ($accessToken) {
            try {
                $available = $channels->listMine($accessToken);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            // MANTIKSAL KORUMA 1:
            // Eğer kullanıcı zaten giriş yapmışsa (veya sistemde gerçek mail hesabı varsa) ve seçilen hesap
            // bir Google Marka Hesabı (@pages.plusgoogle.com — yani YouTube Kanalı) ise:
            // ASLA yeni bir Üye (User) oluşturma! Bu kanalı mevcut ana üyeye bağla!
            $currentAuthUser = Auth::user();
            if ($isBrandAccount && !$currentAuthUser) {
                $primaryUser = User::where('email', 'not like', '%@pages.plusgoogle.com')
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();
                if ($primaryUser) {
                    $currentAuthUser = $primaryUser;
                }
            }

            if ($isBrandAccount && $currentAuthUser) {
                $user = $currentAuthUser;
                if ($accessToken) {
                    $storedToken = YouTubeOAuthToken::firstOrNew(['user_id' => $user->id]);
                    $storedToken->access_token_enc = Crypt::encryptString($accessToken);
                    $storedToken->scopes = 'openid email profile https://www.googleapis.com/auth/youtube.readonly';
                    $storedToken->expires_at = now()->addHour();
                    $storedToken->save();
                }
            } else {
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

                if (!$isBrandAccount) {
                    $this->mergeBrandAccountUsersInto($user);
                }
            }

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

    protected function verifyFirebaseToken(string $idToken): ?array
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

    /**
     * Yanlışlıkla @pages.plusgoogle.com (Marka Hesabı / YouTube Kanalı) olarak açılmış sözde üye kayıtlarını
     * gerçek ana Google üyesinin (ör. @gmail.com) altına kanal olarak birleştirir ve sahte üye satırını siler.
     */
    private function mergeBrandAccountUsersInto(User $primaryUser): void
    {
        $brandUsers = User::where('email', 'like', '%@pages.plusgoogle.com')
            ->where('id', '<>', $primaryUser->id)
            ->get();

        if ($brandUsers->isEmpty()) {
            return;
        }

        $mergedGoogleIds = (array) ($primaryUser->google_channel_ids ?? []);

        foreach ($brandUsers as $brandUser) {
            try {
                foreach ((array) ($brandUser->google_channel_ids ?? []) as $gid) {
                    $mergedGoogleIds[] = $gid;
                }

                UserChannelLink::where('user_id', $brandUser->id)->update([
                    'user_id'  => $primaryUser->id,
                    'ended_at' => now(),
                ]);

                UserVideoSelection::where('user_id', $brandUser->id)->update([
                    'user_id' => $primaryUser->id,
                ]);

                DB::table('youtube_oauth_tokens')->where('user_id', $brandUser->id)->delete();
                $brandUser->delete();
            } catch (\Throwable $e) {
                Log::warning('Marka hesabı birleştirme hatası: ' . $e->getMessage());
            }
        }

        $mergedGoogleIds = array_values(array_unique(array_filter($mergedGoogleIds)));
        $primaryUser->update(['google_channel_ids' => $mergedGoogleIds]);
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
