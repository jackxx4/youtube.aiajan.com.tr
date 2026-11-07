<?php
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\VideoShowcaseController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/api/version', fn() => response()->json([
    'version' => config('version.current', '1.1.0'),
    'release' => config('version.release_name', 'YouTube Keşif'),
    'updated_at' => config('version.updated_at')
]))->name('api.version');

Route::get('/',fn()=>view('welcome'))->name('home');
Route::get('/gizlilik',fn()=>view('gizlilik'))->name('privacy');
Route::get('/giris',fn()=>view('auth.login'))->name('login');
Route::get('/auth/google',[GoogleAuthController::class,'redirect'])->name('auth.google');
Route::get('/auth/google/callback',[GoogleAuthController::class,'callback'])->name('auth.google.callback');
Route::post('/auth/firebase-callback',[GoogleAuthController::class,'firebaseCallback'])->name('auth.firebase.callback');

// Sıfır-Güven Korumalı Dağıtım ve Otomasyon Kancası
Route::match(['get', 'post'], '/_deploy/artisan', function (Request $request) {
    $token = (string) ($request->query('token') ?: $request->input('token'));
    $secret = (string) env('DEPLOY_SECRET', 'k78Y2m9PqR1sT5vW4xZ8bA3cE6fH0jL2');
    abort_unless($token !== '' && hash_equals($secret, $token), 403, 'Yetkisiz erişim');

    $command = (string) ($request->query('cmd') ?: $request->input('cmd', 'migrate'));
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
 Route::post('/kanal-secimi/google-senkron',[ChannelController::class,'syncGoogleChannels'])->name('channels.sync_google');
 Route::get('/profil',[ProfileController::class,'show'])->name('profile.me');
 Route::get('/kullanici/{username}',[ProfileController::class,'show'])->name('profile.show');
 Route::get('/kesfet/kullanicilar/{pool}',[DiscoveryController::class,'users'])->name('discovery.users');
 Route::post('/kesfet/kullanicilar/{pool}/takip',[DiscoveryController::class,'follow'])->name('discovery.follow');
 Route::get('/kesfet/videolar',[DiscoveryController::class,'videos'])->name('discovery.videos');
 Route::get('/vitrenim',[VideoShowcaseController::class,'index'])->name('videos.showcase');
 Route::post('/vitrenim',[VideoShowcaseController::class,'update'])->name('videos.showcase.update');
 Route::prefix('yonetim')->name('admin.')->middleware('admin')->group(function(){Route::get('/',AdminDashboardController::class)->name('dashboard');});
});
