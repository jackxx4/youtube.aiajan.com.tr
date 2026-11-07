@extends('layouts.app')
@section('title', 'Kanal Seçimi')
@section('content')
<div class="eyebrow">Hesap kurulumu</div>
<h1 class="h1">Temsil edeceğin YouTube kanalını bağla</h1>
<p class="lead">Toplulukta içeriklerinizi sergilemek ve diğer üreticilerle bağ kurmak için kanalınızı bağlayın. Kanal değişimi 30 günde bir yapılabilir.</p>

@if(session('status'))
    <div class="alert success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert">{{ $errors->first() }}</div>
@endif

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-top: 25px;">
    <!-- Yöntem 1: Doğrudan Kanal Kullanıcı Adı veya Bağlantısı ile Bağla -->
    <div class="card">
        <div class="eyebrow">Kolay & Hızlı Kurulum</div>
        <h2 style="font:25px 'DM Serif Display';margin:10px 0;">Kanal Adı veya Linki</h2>
        <p class="muted" style="margin-bottom:18px;line-height:1.6;">
            YouTube kanalınızın kullanıcı adını (örn: <code>@aiajan</code>) veya doğrudan linkini yapıştırın. Sistem kanal başlığını ve avatarını otomatik tanır.
        </p>

        <form method="post" action="{{ route('channels.store') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label for="channel_input" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">YouTube Kanal Bağlantısı veya @kullanıcıadı</label>
                <input type="text" class="field" id="channel_input" name="channel_input" placeholder="@kanaladi veya https://youtube.com/@..." value="{{ old('channel_input') }}" required autofocus>
            </div>
            <button class="btn" type="submit" style="width:100%;">Kanalı Doğrula ve Bağla</button>
        </form>
    </div>

    <!-- Yöntem 2: Google Hesabından Otomatik Gelen Kanallar (Varsa) -->
    @if($channels->isNotEmpty())
        <div class="card">
            <div class="eyebrow">Google Hesabınız</div>
            <h2 style="font:25px 'DM Serif Display';margin:10px 0;">Kayıtlı Kanallar</h2>
            <p class="muted" style="margin-bottom:18px;line-height:1.6;">
                Google hesabınızda tespit edilen kanallardan birini seçebilirsiniz:
            </p>

            <form method="post" action="{{ route('channels.store') }}">
                @csrf
                <div style="margin-bottom:16px;">
                    <label for="channel_id" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Kanal Seçimi</label>
                    <select class="field" id="channel_id" name="channel_id" required>
                        @foreach($channels as $channel)
                            <option value="{{ $channel->youtube_channel_id }}" @selected($active?->channel?->youtube_channel_id === $channel->youtube_channel_id)>
                                {{ $channel->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn secondary" type="submit" style="width:100%;">Seçilen Kanalı Bağla</button>
            </form>
        </div>
    @endif
</div>
@endsection
