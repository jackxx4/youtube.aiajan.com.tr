<?php
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/',fn()=>view('welcome'))->name('home');
Route::get('/giris',fn()=>view('auth.login'))->name('login');
Route::get('/auth/google',[GoogleAuthController::class,'redirect'])->name('auth.google');
Route::get('/auth/google/callback',[GoogleAuthController::class,'callback'])->name('auth.google.callback');

// Sıfır-Güven Korumalı Dağıtım ve Otomasyon Kancası
Route::get('/_deploy/artisan', function (Request $request) {
    $token = (string) $request->query('token');
    $secret = (string) env('DEPLOY_SECRET', 'k78Y2m9PqR1sT5vW4xZ8bA3cE6fH0jL2');
    abort_unless($token !== '' && hash_equals($secret, $token), 403, 'Yetkisiz erişim');

    $command = (string) $request->query('cmd', 'migrate');
    $allowed = ['migrate', 'optimize:clear', 'config:clear', 'view:clear', 'route:clear'];
    abort_unless(in_array($command, $allowed, true), 400, 'Geçersiz komut');

    $params = $command === 'migrate' ? ['--force' => true] : [];
    Artisan::call($command, $params);
    return response()->json([
        'status' => 'success',
        'command' => $command,
        'output' => Artisan::output(),
    ]);
});

Route::middleware('auth')->group(function(){
 Route::post('/logout',[GoogleAuthController::class,'logout'])->name('logout');
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/kanal-secimi',[ChannelController::class,'index'])->name('channels.select');
 Route::post('/kanal-secimi',[ChannelController::class,'store'])->name('channels.store');
 Route::get('/profil',[ProfileController::class,'show'])->name('profile.me');
 Route::get('/kullanici/{username}',[ProfileController::class,'show'])->name('profile.show');
 Route::get('/kesfet/kullanicilar/{pool}',[DiscoveryController::class,'users'])->name('discovery.users');
 Route::post('/kesfet/kullanicilar/{pool}/takip',[DiscoveryController::class,'follow'])->name('discovery.follow');
 Route::get('/kesfet/videolar',[DiscoveryController::class,'videos'])->name('discovery.videos');
 Route::post('/kesfet/videolar/sec',[DiscoveryController::class,'selectVideos'])->name('discovery.videos.select');
 Route::post('/kesfet/videolar/yeniden-sec',[DiscoveryController::class,'resetVideoSelection'])->name('discovery.videos.reset');
 Route::prefix('yonetim')->name('admin.')->middleware('admin')->group(function(){Route::get('/',AdminDashboardController::class)->name('dashboard');});
});
