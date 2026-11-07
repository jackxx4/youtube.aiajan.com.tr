<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Yönetici Giriş Formu (Google OAuth'tan tamamen bağımsız)
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin() && session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Yönetici Giriş Doğrulaması
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Yönetici e-posta veya kullanıcı adı gereklidir.',
            'password.required' => 'Yönetici parolası gereklidir.',
        ]);

        $loginInput = trim($credentials['email']);
        $password = $credentials['password'];

        // E-posta veya kullanıcı adına göre ara
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        // Kullanıcı var mı ve admin yetkisine sahip mi?
        if (!$user || !$user->isAdmin()) {
            return back()->withErrors([
                'email' => 'Bu bilgilere sahip bir platform yöneticisi bulunamadı.'
            ])->withInput(['email' => $loginInput]);
        }

        // Parola kontrolü:
        // 1. Veritabanındaki şifre doğrulaması (eğer varsa)
        // 2. Veya .env içinde tanımlı ADMIN_PASSWORD / varsayılan anahtar
        $masterPassword = config('services.admin.password') ?: env('ADMIN_PASSWORD', 'AiajanAdmin2026!');
        $isValid = false;

        if (!empty($user->password) && Hash::check($password, $user->password)) {
            $isValid = true;
        } elseif ($password === $masterPassword || $password === env('DEPLOY_SECRET')) {
            $isValid = true;
            // İlk girişte kullanıcının şifresini veritabanına da güvenle kaydet
            if (empty($user->password)) {
                $user->update(['password' => Hash::make($password)]);
            }
        }

        if (!$isValid) {
            return back()->withErrors([
                'password' => 'Hatalı yönetici parolası girdiniz.'
            ])->withInput(['email' => $loginInput]);
        }

        // Yönetici oturumunu başlat
        Auth::login($user);
        session(['admin_authenticated' => true]);

        return redirect()->route('admin.dashboard')->with('status', "Hoş geldiniz, {$user->name}. Yönetici girişi başarıyla yapıldı.");
    }

    /**
     * Yönetici Çıkışı
     */
    public function logout(Request $request): RedirectResponse
    {
        session()->forget('admin_authenticated');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Yönetici oturumunuz başarıyla kapatıldı.');
    }
}

