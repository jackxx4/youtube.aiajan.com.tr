@extends('layouts.app')
@section('title','Profil')
@section('content')
<div class="eyebrow">Üretici profili</div><h1 class="h1">Profil</h1><div class="card" style="max-width:650px;margin-top:22px"><div class="person">@if($channel?->thumbnail_url)<img class="avatar" src="{{ $channel->thumbnail_url }}" alt="Kanal görseli">@endif<div><h2 style="font:28px 'DM Serif Display';margin:0">{{ $channel?->title ?? 'Kanal bağlanmamış' }}</h2><div class="muted">{{ '@'.$profile->username }}</div></div></div><p class="foot">Profilde abone sayısı, toplam görüntülenme veya takipçi listesi gösterilmez.</p></div>
@endsection