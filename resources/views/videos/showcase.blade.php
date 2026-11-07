@extends('layouts.app')
@section('title', 'Video Vitrinim')
@section('content')
<div style="max-width:960px;margin:0 auto;">

    <!-- Üst Başlık & Aksiyonlar -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px;">
        <div>
            <p class="eyebrow">Kanal Yönetimi · Video Vitrini</p>
            <h1 class="h1" style="margin:4px 0 6px;">Video Vitrinim</h1>
            <p class="lead" style="margin-bottom:0;">
                Vitrininiz, topluluk keşif havuzunda diğer üreticilerin izleyeceği <strong>en fazla 3 videonuzdan</strong> oluşur.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <form method="POST" action="{{ route('videos.showcase.sync') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn secondary" style="padding:9px 15px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                    <span>🔄 Son Videoları Yeniden Tara</span>
                </button>
            </form>
            <a href="{{ route('dashboard') }}" class="btn secondary" style="padding:9px 15px;font-size:13px;">
                ← Genel Bakış'a Dön
            </a>
        </div>
    </div>

    <!-- Bağlı Kanal Bilgi Şeridi -->
    <div class="card" style="margin:20px 0 28px;padding:14px 20px;background:#fbf7f2;border-left:4px solid var(--rust);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;">
        <div style="display:flex;align-items:center;gap:12px;">
            @if($channel->thumbnail_url)
                <img src="{{ $channel->thumbnail_url }}" alt="{{ $channel->title }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid var(--line);">
            @else
                <div style="width:40px;height:40px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);">
                    {{ mb_substr($channel->title, 0, 1) }}
                </div>
            @endif
            <div>
                <strong>Bağlı Kanal:</strong> {{ $channel->title }}
                <span class="muted" style="margin-left:6px;">({{ $channel->custom_url ?: ('ID: ' . $channel->youtube_channel_id) }})</span>
            </div>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <span id="vitrin-pill" style="font-weight:700;color:var(--wine);background:var(--sand);padding:4px 12px;border-radius:12px;font-size:12px;">
                {{ count($selectedIds) }} / 3 Video Vitrinde
            </span>
            @if(!empty($selectedIds))
                <form method="POST" action="{{ route('videos.showcase.clear') }}" onsubmit="return confirm('Vitrininizdeki tüm videoları kaldırmak istediğinize emin misiniz?');" style="margin:0;">
                    @csrf
                    <button type="submit" style="background:transparent;border:none;color:#c93b2b;font-size:12px;text-decoration:underline;cursor:pointer;padding:4px 0;">
                        🗑️ Tümünü Temizle
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- 10 VİDEO LİSTESİ VE 3 SEÇİM FORMU (ANA BÖLÜM) -->
    <div class="card" style="padding:26px 28px;margin-bottom:32px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
            <div>
                <h2 style="font:22px 'DM Serif Display',serif;margin:0 0 4px;color:var(--wine);">
                    Kanalınızın Son Videoları (En Fazla 3 Video Seçin)
                </h2>
                <p class="muted" style="margin:0;font-size:13px;">
                    Aşağıdaki 10 video arasından vitrinde öne çıkarmak istediğiniz <strong>3 videoyu işaretleyin</strong> ve alttaki butondan kaydedin.
                </p>
            </div>
            <span id="selected-counter-badge" style="font-weight:700;font-size:13px;color:var(--rust);background:#fbf3ee;padding:6px 14px;border-radius:16px;border:1px solid #ebd3c5;">
                {{ count($selectedIds) }} / 3 seçildi
            </span>
        </div>

        @if($channelVideos->isEmpty())
            <div style="text-align:center;padding:36px 18px;background:#fffdfa;border:2px dashed #ebd3c5;border-radius:12px;">
                <div style="font-size:40px;margin-bottom:10px;">📺</div>
                <h3 style="font:20px 'DM Serif Display',serif;margin:0 0 6px;color:var(--wine);">
                    Kanalınıza Ait Video Bulunamadı
                </h3>
                <p class="muted" style="max-width:500px;margin:0 auto 18px;font-size:13px;line-height:1.6;">
                    YouTube kanalınızda herkese açık (public) yatay formatta videolar olduğundan emin olun. Videolarınızı yükledikten sonra aşağıdaki butonla hemen tarayabilirsiniz.
                </p>
                <form method="POST" action="{{ route('videos.showcase.sync') }}" style="display:inline-block;">
                    @csrf
                    <button type="submit" class="btn" style="padding:10px 20px;">
                        🔄 Kanal Videolarını Şimdi Tara
                    </button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('videos.showcase.update') }}" id="showcase-form">
                @csrf

                <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:0;">
                    @foreach($channelVideos as $video)
                        @php
                            $isSelected = in_array($video->id, $selectedIds);
                        @endphp
                        <label class="card video-choice-card" style="cursor:pointer;margin:0;padding:0;overflow:hidden;border:2px solid {{ $isSelected ? 'var(--rust)' : 'var(--line)' }};border-radius:12px;background:{{ $isSelected ? '#fdf8f5' : '#fff' }};transition:all 0.15s ease;display:flex;flex-direction:column;">
                            <!-- Video Thumbnail & Seçim İşareti -->
                            <div style="position:relative;width:100%;aspect-ratio:16/9;background:#1a1412;">
                                @if($video->thumbnail_url)
                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" style="width:100%;height:100%;object-fit:cover;display:block;">
                                @else
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;">▶</div>
                                @endif

                                <!-- Checkbox -->
                                <div style="position:absolute;top:10px;left:10px;z-index:2;background:rgba(0,0,0,0.65);padding:6px;border-radius:8px;backdrop-filter:blur(4px);display:flex;align-items:center;">
                                    <input type="checkbox"
                                        name="video_ids[]"
                                        value="{{ $video->id }}"
                                        class="video-choice-input"
                                        {{ $isSelected ? 'checked' : '' }}
                                        style="width:20px;height:20px;accent-color:var(--rust);cursor:pointer;margin:0;">
                                </div>

                                <!-- Vitrinde Rozeti -->
                                <span class="badge-vitrin" style="position:absolute;top:10px;right:10px;z-index:2;background:var(--rust);color:#fff;font-size:11px;font-weight:700;padding:4px 8px;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:{{ $isSelected ? 'inline-block' : 'none' }};">
                                    ⭐ Vitrinde
                                </span>
                            </div>

                            <!-- Başlık & Bilgiler -->
                            <div style="padding:14px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                                <div>
                                    <h4 style="font-size:14px;font-weight:600;line-height:1.45;color:var(--ink);margin:0 0 8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" title="{{ $video->title }}">
                                        {{ $video->title }}
                                    </h4>
                                    @if($video->published_at)
                                        <div class="muted" style="font-size:11px;margin-bottom:8px;">
                                            Yayınlanma: {{ $video->published_at->translatedFormat('d M Y') }}
                                        </div>
                                    @endif
                                </div>

                                <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid var(--line);font-size:12px;">
                                    <span class="muted status-label" style="font-weight:600;color:{{ $isSelected ? 'var(--rust)' : 'var(--muted)' }};">
                                        {{ $isSelected ? '✓ Vitrinde Seçili' : 'Seçilmedi' }}
                                    </span>
                                    <a href="https://www.youtube.com/watch?v={{ $video->youtube_video_id }}" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();" style="color:var(--rust);text-decoration:underline;">
                                        İzle ↗
                                    </a>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <!-- Kaydetme Aksiyon Barı -->
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-top:24px;padding-top:18px;border-top:1px solid var(--line);">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button type="submit" class="btn" id="save-btn" style="padding:12px 28px;font-size:14px;display:inline-flex;align-items:center;gap:8px;">
                            <span>💾 Vitrini Kaydet</span>
                            <span id="btn-count-tag" style="background:rgba(255,255,255,0.25);padding:2px 8px;border-radius:10px;font-size:12px;">{{ count($selectedIds) }}/3</span>
                        </button>
                    </div>
                    <span class="muted" style="font-size:13px;">
                        İpucu: Vitrininize en fazla 3 video ekleyebilirsiniz. Dilediğiniz zaman değiştirebilirsiniz.
                    </span>
                </div>
            </form>
        @endif
    </div>

    <!-- EK YÖNTEM: BAĞLANTI İLE VİDEO EKLEME -->
    <div class="card" style="padding:22px 26px;background:var(--cream);border:1px solid var(--line);border-radius:14px;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div style="font-size:28px;line-height:1;">🔗</div>
            <div style="flex:1;">
                <h3 style="font:20px 'DM Serif Display',serif;margin:0 0 6px;color:var(--wine);">
                    Listede Olmayan Bir Videoyu Bağlantı ile Ekle
                </h3>
                <p class="muted" style="margin-bottom:14px;font-size:13px;line-height:1.5;">
                    Kanalınıza ait listede görünmeyen özel veya eski bir videoyu bağlantısını yapıştırarak doğrudan vitrininize ekleyebilirsiniz.
                </p>

                <form method="POST" action="{{ route('videos.showcase.add_url') }}" style="display:flex;gap:10px;flex-wrap:wrap;">
                    @csrf
                    <input type="text"
                        name="video_url"
                        class="field"
                        placeholder="Örn: https://www.youtube.com/watch?v=..."
                        value="{{ old('video_url') }}"
                        required
                        style="flex:1;min-width:260px;margin:0;">
                    <button type="submit" class="btn secondary" style="white-space:nowrap;padding:11px 22px;font-size:13px;">
                        <span>＋ Vitrine Ekle</span>
                    </button>
                </form>

                <div style="margin-top:10px;font-size:12px;color:var(--muted);display:flex;gap:12px;flex-wrap:wrap;">
                    <span>✓ Normal (yatay) videolar</span>
                    <span>•</span>
                    <span>✗ Shorts videoları kabul edilmez</span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- İstemci Tarafı Kontrolleri: Maks. 3 Video Seçimi -->
<script>
(function() {
    const checkboxes = Array.from(document.querySelectorAll('.video-choice-input'));
    const counterBadge = document.getElementById('selected-counter-badge');
    const btnCountTag = document.getElementById('btn-count-tag');

    function updateSelection() {
        const checked = checkboxes.filter(cb => cb.checked);
        const count = checked.length;

        if (counterBadge) {
            counterBadge.textContent = count + ' / 3 seçildi';
        }
        if (btnCountTag) {
            btnCountTag.textContent = count + '/3';
        }

        checkboxes.forEach(cb => {
            const card = cb.closest('.video-choice-card');
            const badge = card ? card.querySelector('.badge-vitrin') : null;
            const statusLabel = card ? card.querySelector('.status-label') : null;

            if (cb.checked) {
                if (card) {
                    card.style.borderColor = 'var(--rust)';
                    card.style.background = '#fdf8f5';
                }
                if (badge) badge.style.display = 'inline-block';
                if (statusLabel) {
                    statusLabel.textContent = '✓ Vitrinde Seçili';
                    statusLabel.style.color = 'var(--rust)';
                }
            } else {
                // 3 video seçildiyse geri kalanları devre dışı bırak
                cb.disabled = (count >= 3);
                if (card) {
                    card.style.borderColor = 'var(--line)';
                    card.style.background = '#fff';
                    card.style.opacity = (count >= 3) ? '0.65' : '1';
                }
                if (badge) badge.style.display = 'none';
                if (statusLabel) {
                    statusLabel.textContent = 'Seçilmedi';
                    statusLabel.style.color = 'var(--muted)';
                }
            }
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateSelection));
    updateSelection();
})();
</script>
@endsection
