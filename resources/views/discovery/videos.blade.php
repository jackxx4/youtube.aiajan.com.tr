@extends('layouts.app')
@section('title','Video Keşfi')
@section('content')
<div class="eyebrow">Video havuzu</div><h1 class="h1">İçeriklere göz at</h1><p class="lead">Videoları kendi tercihinle açabilirsin. İzleme, platform avantajı veya YouTube etkileşimi için bir koşul değildir. YouTube oynatıcısının standart kontrolleri korunur.</p>
@if($videos->isEmpty())<div class="card" style="margin-top:22px"><p>Henüz senkronize edilmiş video bulunmuyor. Kanal senkronizasyonu etkinleştirildiğinde öneriler burada görünecek.</p></div>@else
<div class="grid">@foreach($videos as $video)<article class="card"><iframe class="video" src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_video_id }}" title="{{ $video->title }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe><h3 style="font:21px 'DM Serif Display';margin:13px 0 6px">{{ $video->title }}</h3><div class="muted">{{ $video->channel?->title }} · {{ $video->published_at?->format('d.m.Y') }}</div></article>@endforeach</div>
@endif
@endsection