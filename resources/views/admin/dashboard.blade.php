@extends('layouts.app')
@section('title','Yönetim')
@section('content')
<div class="eyebrow">Yönetici alanı</div><h1 class="h1">Platform özeti</h1><p class="lead">Bu bölüm yalnızca yönetici hesaplarına açıktır. Kanal metrikleri normal kullanıcı profillerinde gösterilmez.</p>
<div class="grid">
 <div class="card"><div class="eyebrow">Kullanıcı</div><div class="stat">{{ $users }}</div></div>
 <div class="card"><div class="eyebrow">Bağlı kanal</div><div class="stat">{{ $channels }}</div></div>
 <div class="card"><div class="eyebrow">Aktif destekçi</div><div class="stat">{{ $supporters }}</div></div>
</div>
<div class="card" style="margin-top:20px"><h2 style="font:25px 'DM Serif Display'">Aylık YouTube metrikleri</h2>
@if($metrics->isEmpty())<p class="muted">Henüz aylık metrik kaydı yok. Metrik toplama entegrasyonu ayrıca yapılandırılmalıdır.</p>
@else
@foreach($metrics as $metric)<div style="padding:12px 0;border-bottom:1px solid var(--line)"><strong>{{ $metric->metric_month }}</strong><div class="muted">Kanal #{{ $metric->youtube_channel_id }} · Abone: {{ $metric->subscriber_count ?? '—' }} · Görüntülenme: {{ $metric->view_count ?? '—' }}</div></div>@endforeach
@endif
</div>
@endsection