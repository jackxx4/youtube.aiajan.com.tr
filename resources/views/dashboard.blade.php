@extends('layouts.app')
@section('title', 'Genel Bakış')
@section('content')
<div class="eyebrow">Kontrol paneli</div>
<h1 class="h1">Merhaba, {{ $user->name }}</h1>
<p class="lead">Bugünkü keşif alanın hazır. Platform takipleri YouTube aboneliği değildir.</p>

@if(!$channel)
    <div class="card" style="margin-top:24px;border-left:4px solid var(--rust);">
        <h2 style="font:25px 'DM Serif Display',serif;margin:0 0 8px;">YouTube kanalını bağla</h2>
        <p class="muted" style="margin-bottom:16px;">Profilini oluşturmak ve videolarını vitrinde sergilemek için Google hesabından bir kanal bağla.</p>
        <a class="btn" href="{{ route('channels.select') }}">Kanal seç</a>
    </div>
@else
    <!-- Üst İstatistik & Durum Kartları -->
    <div class="grid" style="margin-top:22px;">
        <div class="card">
            <div class="eyebrow">Bağlı kanal</div>
            <div class="person" style="margin-top:12px;">
                @if($channel->thumbnail_url)
                    <img class="avatar" src="{{ $channel->thumbnail_url }}" alt="{{ $channel->title }}">
                @else
                    <div class="avatar" style="background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);">
                        {{ mb_substr($channel->title, 0, 1) }}
                    </div>
                @endif
                <div>
                    <strong>{{ $channel->title }}</strong>
                    <div class="muted">{{ '@' . $user->username }}</div>
                    @if($canChangeChannel)
                        <a class="muted" href="{{ route('channels.select') }}" style="font-size:12px;text-decoration:underline;margin-top:3px;display:inline-block;">Kanalı değiştir</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="eyebrow">Bugünkü takipler</div>
            <div class="stat">{{ $followsToday }} / 6</div>
            <div class="muted">Normal ve destekçi keşfi toplamı</div>
        </div>

        <div class="card">
            <div class="eyebrow">Vitrin Durumu</div>
            <div class="stat" style="color:var(--rust);">{{ count($selectedVideoIds) }} / 3 Video</div>
            <div style="margin-top:8px;">
                <a href="{{ route('videos.showcase') }}" class="muted" style="font-size:12px;text-decoration:underline;">Vitrini Düzenle ↗</a>
            </div>
        </div>
    </div>

    <!-- EKİLİ VİDEOLAR / VİTRİN BÖLÜMÜ (KART ŞEKLİNDE) -->
    <section style="margin-top:40px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
            <div>
                <div class="eyebrow" style="margin-bottom:2px;">Kanal İçerikleri & Vitrin</div>
                <h2 style="font:26px 'DM Serif Display',serif;margin:0;color:var(--wine);">Ekli Kanal Videolarım</h2>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                @if($showcaseVideos->isNotEmpty())
                    <span style="background:var(--sand);border:1px solid var(--line);padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;color:var(--rust);">
                        ⭐ {{ $showcaseVideos->count() }}/3 Vitrinde Yayında
                    </span>
                @endif
                <a href="{{ route('videos.showcase') }}" class="btn" style="padding:9px 18px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                    <span>⚙️ Vitrini Yönet / Video Ekle</span>
                </a>
            </div>
        </div>

        @if($myVideos->isEmpty())
            <!-- Henüz Video Eklenmemişse Kart -->
            <div class="card" style="padding:36px 24px;text-align:center;background:linear-gradient(to right,#fffdf8,#fcf6ee);border:2px dashed #d6bca8;">
                <div style="font-size:42px;margin-bottom:10px;">🎬</div>
                <h3 style="font:24px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Henüz Ekli Video Bulunmuyor</h3>
                <p class="muted" style="max-width:520px;margin:0 auto 20px;line-height:1.6;font-size:14px;">
                    Topluluk keşif havuzunda öne çıkarmak istediğiniz <strong>en fazla 3 videoyu</strong> kanalınızdan tek tıkla çekebilir veya doğrudan YouTube linki yapıştırarak ekleyebilirsiniz.
                </p>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="{{ route('videos.showcase') }}" class="btn" style="padding:11px 22px;font-size:14px;">
                        <span>+ Videoları Çek veya Link Ekle</span>
                    </a>
                </div>
            </div>
        @else
            <!-- Ekli Videolar Grid (Kart Şeklinde) -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:20px;">
                @foreach($myVideos as $video)
                @php $isVitrin = in_array($video->id, $selectedVideoIds, true); @endphp
                <article class="card" style="padding:0;overflow:hidden;border-radius:14px;border:2px solid {{ $isVitrin ? 'var(--rust)' : 'var(--line)' }};display:flex;flex-direction:column;box-shadow:0 3px 12px rgba(32,26,23,0.04);transition:transform .15s ease,box-shadow .15s ease;">
                    
                    <!-- Thumbnail & Rozet -->
                    <div style="position:relative;width:100%;aspect-ratio:16/9;overflow:hidden;background:#201a17;">
                        @if($video->thumbnail_url)
                            <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;">
                        @else
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:32px;">▶</div>
                        @endif

                        <!-- Durum Rozeti -->
                        <div style="position:absolute;top:10px;left:10px;z-index:2;">
                            @if($isVitrin)
                                <span style="background:var(--rust);color:#fff;font-size:11px;font-weight:700;padding:4px 9px;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,.35);letter-spacing:0.3px;">
                                    ⭐ Vitrinde Yayında
                                </span>
                            @else
                                <span style="background:rgba(32,26,23,0.72);color:#fff;font-size:11px;font-weight:600;padding:4px 8px;border-radius:6px;backdrop-filter:blur(4px);">
                                    Kanal Videosu
                                </span>
                            @endif
                        </div>

                        <!-- Oynat Overlay Butonu -->
                        <button type="button" class="play-video-btn" data-video-id="{{ $video->youtube_video_id }}" data-video-title="{{ $video->title }}" style="position:absolute;inset:0;width:100%;height:100%;background:rgba(0,0,0,0.18);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s ease;" title="Videoyu Önizle">
                            <span style="width:48px;height:48px;border-radius:50%;background:rgba(184,80,48,0.92);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;padding-left:3px;box-shadow:0 4px 14px rgba(0,0,0,.4);transition:transform .15s ease;">▶</span>
                        </button>
                    </div>

                    <!-- Kart Gövdesi -->
                    <div style="padding:15px 16px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                        <div>
                            <h3 style="font-size:14px;font-weight:600;line-height:1.45;color:var(--wine);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0 0 8px;min-height:40px;" title="{{ $video->title }}">
                                {{ $video->title }}
                            </h3>
                            <div class="muted" style="font-size:12px;display:flex;align-items:center;gap:6px;margin-bottom:14px;">
                                <span>📺 {{ $channel->title }}</span>
                                @if($video->published_at)
                                    <span>•</span>
                                    <span>{{ $video->published_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Aksiyon Butonları -->
                        <div style="display:flex;gap:8px;align-items:center;padding-top:10px;border-top:1px solid var(--line);">
                            <button type="button" class="btn secondary play-video-btn" data-video-id="{{ $video->youtube_video_id }}" data-video-title="{{ $video->title }}" style="flex:1;padding:7px 12px;font-size:12px;display:inline-flex;align-items:center;justify-content:center;gap:5px;">
                                <span>▶ Önizle</span>
                            </button>
                            <a href="https://www.youtube.com/watch?v={{ $video->youtube_video_id }}" target="_blank" rel="noopener noreferrer" class="btn secondary" style="padding:7px 12px;font-size:12px;display:inline-flex;align-items:center;justify-content:center;gap:4px;" title="YouTube'da Yeni Sekmede Aç">
                                <span>YouTube ↗</span>
                            </a>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        @endif
    </section>
@endif

<!-- Keşif Havuzları -->
<div class="grid" style="margin-top:40px;">
    <div class="card">
        <div class="eyebrow">Normal havuz</div>
        <h2 style="font:25px 'DM Serif Display',serif;margin:4px 0 8px;">Üreticileri keşfet</h2>
        <p class="muted">10 profil arasından 3 kişiyi platformda takip et.</p>
        <a href="{{ route('discovery.users','normal') }}" class="btn secondary">Normal keşif</a>
    </div>
    <div class="card">
        <div class="eyebrow">Destekçi havuzu</div>
        <h2 style="font:25px 'DM Serif Display',serif;margin:4px 0 8px;">Destekçileri keşfet</h2>
        <p class="muted">Aktif destekçi üreticiler ayrı listelenir.</p>
        <a href="{{ route('discovery.users','supporter') }}" class="btn secondary">Destekçi keşfi</a>
    </div>
    <div class="card">
        <div class="eyebrow">Video havuzu</div>
        <h2 style="font:25px 'DM Serif Display',serif;margin:4px 0 8px;">Videolara göz at</h2>
        <p class="muted">Video izleme zorunlu görev veya ödül koşulu değildir.</p>
        <a href="{{ route('discovery.videos') }}" class="btn secondary">Video keşfi</a>
    </div>
</div>

<!-- İNTERAKTİF VİDEO ÖNİZLEME MODALI -->
<div id="videoModal" style="display:none;position:fixed;inset:0;background:rgba(20,15,13,0.85);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--cream);border-radius:16px;max-width:760px;width:100%;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,0.5);border:1px solid var(--line);">
        <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);">
            <div id="modalVideoTitle" style="font-weight:700;font-size:15px;color:var(--wine);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding-right:12px;">Video Önizleme</div>
            <button type="button" id="closeModalBtn" style="background:transparent;border:none;font-size:24px;line-height:1;cursor:pointer;color:var(--muted);padding:0 4px;" title="Kapat">×</button>
        </div>
        <div style="position:relative;aspect-ratio:16/9;background:#000;">
            <iframe id="modalVideoIframe" src="" style="width:100%;height:100%;border:none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
        </div>
        <div style="padding:12px 20px;display:flex;justify-content:flex-end;">
            <button type="button" id="closeModalBtn2" class="btn secondary" style="padding:7px 16px;font-size:13px;">Kapat</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('modalVideoIframe');
    const titleEl = document.getElementById('modalVideoTitle');
    const closeBtns = [document.getElementById('closeModalBtn'), document.getElementById('closeModalBtn2')];

    function openModal(videoId, title) {
        if (!modal || !iframe) return;
        titleEl.textContent = title || 'Video Önizleme';
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal || !iframe) return;
        iframe.src = '';
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.play-video-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const vid = this.getAttribute('data-video-id');
            const title = this.getAttribute('data-video-title');
            if (vid) openModal(vid, title);
        });
    });

    closeBtns.forEach(btn => {
        if (btn) btn.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal();
        }
    });
});
</script>
@endsection
