@extends('layouts.app')
@section('title','Kanal Seçimi')
@section('content')
<div class="eyebrow">Hesap kurulumu</div><h1 class="h1">Temsil edeceğin kanalı seç</h1><p class="lead">Bir platform hesabına aynı anda yalnızca bir YouTube kanalı bağlanabilir. Kanal değişimi 30 günde bir yapılabilir.</p>
@if($channels->isEmpty())<div class="card" style="margin-top:22px"><p>Google hesabından kanal listesi alınamadı. YouTube kanal erişimini kontrol ederek yeniden giriş yap.</p><a class="btn" href="{{ route('auth.google') }}">Google ile yeniden bağlan</a></div>@else
<form method="post" action="{{ route('channels.store') }}" class="card" style="max-width:700px;margin-top:22px">@csrf<label for="channel_id">YouTube kanalı</label><select class="field" id="channel_id" name="channel_id" required>@foreach($channels as $channel)<option value="{{ $channel->youtube_channel_id }}" @selected($active?->channel?->youtube_channel_id===$channel->youtube_channel_id)>{{ $channel->title }}</option>@endforeach</select><p class="muted">Seçim, platform profilinde kanal adını ve profil görselini gösterir.</p><button class="btn" type="submit">Kanalı bağla</button></form>
@endif
@endsection