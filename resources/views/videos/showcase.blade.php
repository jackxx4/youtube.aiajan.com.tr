@extends('layouts.app')
@section('title','Video Vitrinim')
@section('content')
<p class="eyebrow">Kanal Yönetimi</p>
<h1 class="h1">Video Vitrinim</h1>
<p class="lead">Topluluğa önermek istediğin <strong>en fazla 3</strong> videoyu seç. Seçtiklerin diğer kullanıcıların keşif akışında gösterilecek.</p>

@if($videos->isEmpty())
    <div class="alert" style="margin-top:24px;">
        Kanalında henüz senkronize edilmiş video yok. Birkaç saat içinde otomatik olarak güncellenecek, ya da yöneticiden senkronizasyon isteğinde bulunabilirsin.
    </div>
@else
<form method="POST" action="{{ route('videos.showcase.update') }}" id="showcaseForm">
    @csrf
    <div style="display:flex;align-items:center;justify-content:space-between;margin:28px 0 16px;">
        <span id="selCount" style="font-weight:600;color:var(--rust);">
            {{ count($selectedIds) }}/3 video seçili
        </span>
        <button class="btn" type="submit">Vitrine Kaydet</button>
    </div>

    <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
        @foreach($videos as $video)
        @php $checked = in_array($video->id, $selectedIds, true); @endphp
        <label for="v{{ $video->id }}" class="video-card {{ $checked ? 'selected' : '' }}" style="cursor:pointer;display:block;border-radius:14px;border:2px solid {{ $checked ? 'var(--rust)' : 'var(--line)' }};background:var(--cream);overflow:hidden;transition:border-color .15s,box-shadow .15s;">
            <div style="position:relative;">
                @if($video->thumbnail_url)
                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" style="width:100%;aspect-ratio:16/9;object-fit:cover;display:block;">
                @else
                    <div style="width:100%;aspect-ratio:16/9;background:var(--sand);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:32px;">▶</div>
                @endif
                <div style="position:absolute;top:8px;right:8px;">
                    <input type="checkbox"
                        name="video_ids[]"
                        id="v{{ $video->id }}"
                        value="{{ $video->id }}"
                        {{ $checked ? 'checked' : '' }}
                        style="width:20px;height:20px;accent-color:var(--rust);cursor:pointer;">
                </div>
            </div>
            <div style="padding:14px;">
                <div style="font-weight:600;font-size:14px;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $video->title }}</div>
                @if($video->published_at)
                    <div class="muted" style="margin-top:5px;">{{ $video->published_at->diffForHumans() }}</div>
                @endif
            </div>
        </label>
        @endforeach
    </div>

    <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
        <button class="btn" type="submit">Vitrine Kaydet</button>
        <a href="{{ route('dashboard') }}" class="btn secondary">Atla</a>
    </div>
</form>

<script>
(function(){
    const MAX = 3;
    const form = document.getElementById('showcaseForm');
    const countEl = document.getElementById('selCount');
    const boxes = form.querySelectorAll('input[type=checkbox]');

    function update(){
        const checked = form.querySelectorAll('input[type=checkbox]:checked');
        const n = checked.length;
        countEl.textContent = n + '/3 video seçili';
        countEl.style.color = n === MAX ? 'var(--wine)' : 'var(--rust)';

        boxes.forEach(cb => {
            const card = cb.closest('label');
            if(cb.checked){
                card.style.borderColor = 'var(--rust)';
                card.style.boxShadow = '0 0 0 3px rgba(173,101,72,.18)';
            } else {
                card.style.borderColor = cb.disabled ? 'var(--line)' : 'var(--line)';
                card.style.boxShadow = '';
                card.style.opacity = cb.disabled ? '0.5' : '1';
            }
            if(!cb.checked) cb.disabled = (n >= MAX);
        });
    }

    boxes.forEach(cb => cb.addEventListener('change', update));
    update();
})();
</script>
@endif
@endsection
