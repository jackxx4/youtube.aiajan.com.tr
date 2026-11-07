<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Yönetici oturumu açık değilse doğrudan Yönetici Giriş sayfasına yönlendir
        if (!$user || !$user->isAdmin() || !session('admin_authenticated')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Yetkisiz erişim. Yönetici oturumu gereklidir.'], 403);
            }

            return redirect()->route('admin.login')->withErrors([
                'auth' => 'Yönetim merkezine erişebilmek için lütfen yönetici girişi yapın.'
            ]);
        }

        return $next($request);
    }
}
