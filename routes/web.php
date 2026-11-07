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
Route::get('/cerez-politikasi',fn()=>view('cerez'))->name('cookies');
Route::get('/kvkk',fn()=>view('kvkk'))->name('kvkk');
Route::get('/iletisim',[App\Http\Controllers\ContactController::class,'index'])->name('contact');
Route::post('/iletisim',[App\Http\Controllers\ContactController::class,'send'])->name('contact.send')->middleware('throttle:5,1');
Route::get('/giris',fn()=>view('auth.login'))->name('login');
Route::get('/auth/google',[GoogleAuthController::class,'redirect'])->name('auth.google');
Route::get('/auth/google/callback',[GoogleAuthController::class,'callback'])->name('auth.google.callback');
Route::post('/auth/firebase-callback',[GoogleAuthController::class,'firebaseCallback'])->name('auth.firebase.callback');

// Sıfır-Güven Korumalı Dağıtım ve Otomasyon Kancası
Route::post('/_deploy/artisan', function (Request $request) {
    $token = (string) $request->input('token');
    $secret = (string) env('DEPLOY_SECRET', '');
    abort_unless($secret !== '' && $token !== '' && hash_equals($secret, $token), 403, 'Yetkisiz erişim');

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
 Route::post('/kesfet/videolar/sec',[DiscoveryController::class,'selectVideos'])->name('discovery.videos.select');
 Route::post('/kesfet/videolar/sifirla',[DiscoveryController::class,'resetVideos'])->name('discovery.videos.reset');
 Route::get('/vitrenim',[VideoShowcaseController::class,'index'])->name('videos.showcase');
 Route::post('/vitrenim',[VideoShowcaseController::class,'update'])->name('videos.showcase.update');
 Route::delete('/vitrenim/videolar/{video}',[VideoShowcaseController::class,'removeSingle'])->name('videos.showcase.remove');
 Route::post('/vitrenim/videolar/{video}/ekle',[VideoShowcaseController::class,'addSingle'])->name('videos.showcase.add_single');
 Route::post('/vitrenim/sifirla',[VideoShowcaseController::class,'clearAll'])->name('videos.showcase.clear');
 Route::post('/vitrenim/senkronize',[VideoShowcaseController::class,'syncChannelVideos'])->name('videos.showcase.sync');
 Route::post('/vitrenim/video-ekle',[VideoShowcaseController::class,'addVideoByUrl'])->name('videos.showcase.add_url');
 Route::prefix('yonetim')->name('admin.')->middleware('admin')->group(function () {
     Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
     Route::post('/kullanici/{user}/rol', [AdminDashboardController::class, 'toggleRole'])->name('users.toggle_role');
     Route::post('/kullanici/{user}/durum', [AdminDashboardController::class, 'toggleStatus'])->name('users.toggle_status');
     Route::post('/onbellek-temizle', [AdminDashboardController::class, 'clearCache'])->name('clear_cache');
 });
});
