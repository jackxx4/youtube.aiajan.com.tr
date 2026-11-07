@extends('layouts.app')
@section('title','Video Keşfi')
@section('content')
@php $selected = $selected ?? session('selected_video_ids'); @endphp
<div class="eyebrow">Video havuzu</div><h1 class="h1">{{ is_array($selected)?'Seçtiğin videolar':'İçeriklere göz at' }}</h1>
<p class="lead">En fazla 5 video seçebilirsin. İzleme tamamen isteğe bağlıdır; izlenme bir ödül, statü veya takip hakkı koşulu değildir. YouTube oynatıcısının standart kontrolleri korunur.</p>
@if(!is_array($selected))
 @if($videos->isEmpty())<div class="card" style="margin-top:22px"><p>Henüz senkronize edilmiş video bulunmuyor. İlk kanal senkronizasyonundan sonra öneriler burada görünecek.</p></div>
 @else
 <form method="post" action="{{ route('discovery.videos.select') }}" class="card" style="margin-top:22px">@csrf
  <div class="grid" style="margin-top:0">@foreach($videos as $video)
   <label class="card" style="cursor:pointer"><input type="checkbox" name="video_ids[]" value="{{ $video->id }}" class="video-choice"><img src="{{ $video->thumbnail_url }}" alt="" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:8px;margin-top:10px"><strong style="display:block;margin-top:10px">{{ $video->title }}</strong><span class="muted">{{ $video->channel?->title }}</span></label>
  @endforeach</div>
  <div style="display:flex;gap:15px;align-items:center;margin-top:20px"><button class="btn" id="video-submit" disabled>Seçimleri aç</button><span class="muted" id="video-count">0 / 5 seçildi</span></div>
 </form>
 <script>
 const boxes=Array.from(document.querySelectorAll('.video-choice'));
 const btn=document.getElementById('video-submit');
 const count=document.getElementById('video-count');
 function sync(){const n=boxes.filter(x=>x.checked).length;count.textContent=n+' / 5 seçildi';btn.disabled=n===0;boxes.forEach(x=>x.disabled=!x.checked&&n>=5);}
 boxes.forEach(x=>x.addEventListener('change',sync));
 </script>
 @endif
@else
 <form method="post" action="{{ route('discovery.videos.reset') }}" style="margin:20px 0">@csrf<button class="btn secondary">Yeni video seç</button></form>
 @if($videos->isEmpty())<div class="card">Seçilen videolar artık erişilebilir değil.</div>@else
 <div class="grid">@foreach($videos as $video)<article class="card"><iframe class="video" src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_video_id }}" title="{{ $video->title }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe><h3 style="font:21px 'DM Serif Display';margin:13px 0 6px">{{ $video->title }}</h3><div class="muted">{{ $video->channel?->title }}</div></article>@endforeach</div>
 @endif
@endif
@endsection