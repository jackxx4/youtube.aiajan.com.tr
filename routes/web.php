<?php
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>view('welcome'))->name('home');
Route::get('/giris',fn()=>view('auth.login'))->name('login');
Route::get('/auth/google',[GoogleAuthController::class,'redirect'])->name('auth.google');
Route::get('/auth/google/callback',[GoogleAuthController::class,'callback'])->name('auth.google.callback');
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
 Route::prefix('yonetim')->name('admin.')->middleware('admin')->group(function(){Route::get('/',AdminDashboardController::class)->name('dashboard');});
});