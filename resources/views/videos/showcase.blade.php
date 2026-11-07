@extends('layouts.app')
@section('title', 'Video Vitrinim')
@section('content')
<div style="max-width:960px;margin:0 auto;">
    <p class="eyebrow">Kanal Yönetimi</p>
    <h1 class="h1">Video Vitrinim</h1>
    <p class="lead">Kanalınızın <strong>son 10 videosu</strong> arasından dilediğiniz <strong>1, 2 veya en fazla 3 videoyu</strong> seçerek vitrininizde sergileyebilirsiniz. Seçtiğiniz videolar keşif akışında diğer üreticilere önerilir.</p>

    <!-- Ayrı Senkronizasyon ve Vitrin Sıfırlama Formları (HTML5 form attribute ile bağlanır) -->
    <form method="POST" action="{{ route('videos.showcase.sync') }}" id="syncForm" style="display:none;">
        @csrf
    </form>
    <form method="POST" action="{{ route('videos.showcase.clear') }}" id="clearForm" style="display:none;" onsubmit="return confirm('Vitrininizdeki tüm videoları kaldırmak istediğinize emin misiniz?');">
        @csrf
    </form>

    <!-- Tekil Video Kaldırma Formları -->
    @foreach($videos as $vForm)
        <form method="POST" action="{{ route('videos.showcase.remove', $vForm->id) }}" id="removeForm{{ $vForm->id }}" style="display:none;" onsubmit="return confirm('“{{ addslashes($vForm->title) }}” videosunu vitrinden kaldırmak istediğinize emin misiniz?');">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    @if($videos->isEmpty())
        <!-- Henüz Video Çekilmemişse Aksiyon Kartı -->
        <div class="card" style="margin-top:24px;padding:36px 24px;text-align:center;background:linear-gradient(to right,#fffdf8,#fcf6ee);border:2px dashed #d6bca8;">
            <div style="font-size:48px;margin-bottom:12px;">🎬</div>
            <h2 style="font:28px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Kanal Videolarınız Henüz Yüklenmedi</h2>
            <p class="muted" style="max-width:540px;margin:0 auto 24px;line-height:1.6;">
                Bağlı YouTube kanalınızdan (<strong>{{ $channel->title }}</strong>) son yüklenen videoları şimdi çekebilir veya öne çıkarmak istediğiniz bir video linkini doğrudan yapıştırabilirsiniz.
            </p>

            <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:28px;">
                <!-- Otomatik Senkronizasyon Butonu -->
                <form method="POST" action="{{ route('videos.showcase.sync') }}" style="display:inline;">
                    @csrf
                    <button class="btn" type="submit" style="padding:14px 28px;font-size:15px;display:inline-flex;align-items:center;gap:8px;">
                        <span>🔄 Videolarımı Şimdi Tara ve Getir</span>
                    </button>
                </form>
                
                <a href="{{ route('dashboard') }}" class="btn secondary" style="padding:14px 22px;font-size:15px;">
                    Şimdilik Atla
                </a>
            </div>

            <!-- Manuel Video Ekleme Kutusu -->
            <div style="max-width:500px;margin:0 auto;text-align:left;border-top:1px solid var(--line);padding-top:20px;">
                <form method="POST" action="{{ route('videos.showcase.add_url') }}">
                    @csrf
                    <label for="video_url" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;color:var(--ink);">
                        Veya Belirli Bir Video Bağlantısı Ekleyin:
                    </label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" class="field" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required style="flex:1;">
                        <button class="btn" type="submit" style="white-space:nowrap;padding:12px 18px;">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Video Seçim Formu -->
        <form method="POST" action="{{ route('videos.showcase.update') }}" id="showcaseForm">
            @csrf
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin:28px 0 16px;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span id="selCount" style="font-weight:700;font-size:15px;color:var(--rust);">
                        {{ count($selectedIds) }}/3 video seçili
                    </span>
                    <span id="selHint" class="muted" style="font-size:13px;">
                        @if(count($selectedIds) === 0)
                            (Seçim yapılmadı — kaydedilirse vitrin boşaltılır)
                        @elseif(count($selectedIds) === 3)
                            (Maksimum vitrin sınırına ulaştınız: 3/3)
                        @else
                            ({{ 3 - count($selectedIds) }} video daha seçebilirsiniz)
                        @endif
                    </span>
                </div>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <button type="submit" form="syncForm" style="background:transparent;border:none;color:var(--muted);font-size:13px;text-decoration:underline;cursor:pointer;padding:6px 0;">
                        🔄 Son Videoları Yeniden Tara
                    </button>
                    @if(count($selectedIds) > 0)
                        <button type="submit" form="clearForm" style="background:transparent;border:none;color:#c93b2b;font-size:13px;text-decoration:underline;cursor:pointer;padding:6px 0;">
                            🗑️ Vitrini Sıfırla
                        </button>
                    @endif
                    <button class="btn" type="submit" id="saveShowcaseBtn">{{ count($selectedIds) === 0 ? 'Vitrini Boşalt ve Kaydet' : 'Vitrini Kaydet' }}</button>
                </div>
            </div>

            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
                @foreach($videos as $video)
                @php $checked = in_array($video->id, $selectedIds, true); @endphp
                <div class="video-card-container" style="display:flex;flex-direction:column;border-radius:14px;border:2px solid {{ $checked ? 'var(--rust)' : 'var(--line)' }};background:var(--cream);overflow:hidden;transition:border-color .15s,box-shadow .15s;">
                    <label for="v{{ $video->id }}" class="video-card {{ $checked ? 'selected' : '' }}" style="cursor:pointer;display:block;flex:1;">
                        <div style="position:relative;">
                            @if($video->thumbnail_url)
                                <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" style="width:100%;aspect-ratio:16/9;object-fit:cover;display:block;">
                            @else
                                <div style="width:100%;aspect-ratio:16/9;background:var(--sand);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:32px;">▶</div>
                            @endif
                            <div style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.5);border-radius:6px;padding:4px;line-height:1;">
                                <input type="checkbox"
                                    name="video_ids[]"
                                    id="v{{ $video->id }}"
                                    value="{{ $video->id }}"
                                    {{ $checked ? 'checked' : '' }}
                                    style="width:20px;height:20px;accent-color:var(--rust);cursor:pointer;display:block;">
                            </div>
                        </div>
                        <div style="padding:14px;">
                            <div style="font-weight:600;font-size:14px;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $video->title }}</div>
                            @if($video->published_at)
                                <div class="muted" style="margin-top:6px;font-size:12px;">{{ $video->published_at->diffForHumans() }}</div>
                            @endif
                        </div>
                    </label>

                    @if($checked)
                        <div style="padding:0 14px 14px;border-top:1px dashed var(--line);background:#fff9f8;">
                            <button type="submit" form="removeForm{{ $video->id }}" class="btn secondary" style="width:100%;padding:7px 10px;font-size:12px;color:#c93b2b;background:#fff5f3;border-color:#f5c6cb;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;" title="Bu videoyu doğrudan vitrinden kaldır">
                                <span>✕ Vitrinden Kaldır</span>
                            </button>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
                <button class="btn" type="submit" id="saveShowcaseBtn2">{{ count($selectedIds) === 0 ? 'Vitrini Boşalt ve Kaydet' : 'Vitrini Kaydet' }}</button>
                <a href="{{ route('dashboard') }}" class="btn secondary">Panoya Dön</a>
            </div>
        </form>

        <!-- Ekstra Video Ekleme Bölümü -->
        <div class="card" style="margin-top:32px;padding:20px 24px;border-top:2px solid var(--line);">
            <div class="eyebrow">Listede Olmayan Bir Video Ekle</div>
            <h3 style="font:20px 'DM Serif Display',serif;margin:4px 0 8px;">Bağlantı ile Video Ekle</h3>
            <p class="muted" style="margin-bottom:14px;font-size:13px;">
                Kanalınızdaki belirli bir videonun linkini yapıştırarak anında vitrininize dahil edebilirsiniz.
            </p>
            <form method="POST" action="{{ route('videos.showcase.add_url') }}" style="display:flex;gap:10px;flex-wrap:wrap;">
                @csrf
                <input type="text" class="field" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required style="flex:1;min-width:240px;">
                <button class="btn secondary" type="submit" style="white-space:nowrap;padding:12px 20px;">Videoyu Ekle</button>
            </form>
        </div>

        <script>
        (function(){
            const MAX = 3;
            const form = document.getElementById('showcaseForm');
            if(!form) return;
            const countEl = document.getElementById('selCount');
            const hintEl = document.getElementById('selHint');
            const saveBtns = [document.getElementById('saveShowcaseBtn'), document.getElementById('saveShowcaseBtn2')];
            const boxes = form.querySelectorAll('input[type=checkbox]');

            function update(){
                const checked = form.querySelectorAll('input[type=checkbox]:checked');
                const n = checked.length;
                countEl.textContent = n + '/3 video seçili';
                countEl.style.color = n === MAX ? 'var(--wine)' : 'var(--rust)';

                if(hintEl) {
                    if (n === 0) {
                        hintEl.textContent = '(Seçim yapılmadı — kaydedilirse vitrin boşaltılır)';
                    } else if (n === MAX) {
                        hintEl.textContent = '(Maksimum vitrin sınırına ulaştınız: 3/3)';
                    } else {
                        hintEl.textContent = '(' + (MAX - n) + ' video daha seçebilirsiniz)';
                    }
                }

                saveBtns.forEach(btn => {
                    if (btn) {
                        btn.disabled = (n > MAX);
                        btn.textContent = (n === 0 ? 'Vitrini Boşalt ve Kaydet' : 'Vitrini Kaydet (' + n + '/3)');
                    }
                });

                boxes.forEach(cb => {
                    const card = cb.closest('.video-card-container') || cb.closest('label');
                    if(card){
                        if(cb.checked){
                            card.style.borderColor = 'var(--rust)';
                            card.style.boxShadow = '0 0 0 3px rgba(173,101,72,.18)';
                        } else {
                            card.style.borderColor = 'var(--line)';
                            card.style.boxShadow = '';
                            card.style.opacity = cb.disabled ? '0.5' : '1';
                        }
                    }
                    if(!cb.checked) cb.disabled = (n >= MAX);
                });
            }

            boxes.forEach(cb => cb.addEventListener('change', update));
            update();
        })();
        </script>
    @endif
</div>
@endsection
