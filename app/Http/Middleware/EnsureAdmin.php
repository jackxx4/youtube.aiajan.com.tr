<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $isAdmin = Auth::guard('admin')->check() || (session('admin_authenticated') && Auth::check() && Auth::user()->isAdmin());

        if (!$isAdmin) {
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
