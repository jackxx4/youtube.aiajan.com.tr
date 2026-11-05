@extends('layouts.app')
@section('title','Giriş')
@section('content')
<div style="max-width:520px;margin:50px auto;text-align:center" class="card"><div class="eyebrow">Hoş geldin</div><h1 class="h1">Hesabına giriş yap</h1><p class="lead" style="margin:0 auto 24px">Google hesabınla devam et. Sonraki adımda erişebildiğin YouTube kanalını seçebilirsin.</p><a class="btn" href="{{ route('auth.google') }}">Google ile devam et</a><p class="foot">Google OAuth izinleri açıkça gösterilir. Şifre bilgilerin platformda tutulmaz.</p></div>
@endsection