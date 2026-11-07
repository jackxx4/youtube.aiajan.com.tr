@extends('layouts.app')
@section('title', 'Sıralı Video Keşfi')
@section('content')
<div style="max-width:1120px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
        <div>
            <div class="eyebrow" style="color:var(--rust);">Topluluk Keşif Havuzu</div>
            <h1 class="h1" style="margin:4px 0 6px;">Sıralı Video Keşfi</h1>
            <p class="lead" style="margin-bottom:0;">
                İçerik üreticilerinin vitrine koyduğu videolar <strong>sırayla ve kesintisiz</strong> oynatılır. Video bittiğinde otomatik olarak sıradakine geçer.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="{{ route('discovery.videos') }}" class="btn secondary" style="padding:10px 16px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                <span>🎲 Yeni Liste Yükle</span>
            </a>
            <a href="{{ route('dashboard') }}" class="btn" style="padding:10px 16px;font-size:13px;">
                <span>Panoya Dön</span>
            </a>
        </div>
    </div>

    @if($videos->isEmpty())
        <div class="card" style="margin-top:24px;padding:40px 24px;text-align:center;background:#fffdf9;border:2px dashed var(--line);">
            <div style="font-size:44px;margin-bottom:12px;">🎬</div>
            <h2 style="font:26px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Henüz Keşif Videosu Bulunmuyor</h2>
            <p class="muted" style="max-width:500px;margin:0 auto 20px;line-height:1.6;font-size:14px;">
                Topluluktaki içerik üreticileri vitrinlerine video ekledikçe burada sırayla izlenebilir hale gelecektir. İlk videoyu siz vitrininize ekleyebilirsiniz!
            </p>
            <a href="{{ route('videos.showcase') }}" class="btn" style="padding:12px 24px;">
                <span>✨ Vitrinime Video Ekle</span>
            </a>
        </div>
    @else
        <!-- Sıralı Oynatıcı Ana Grid (Tiyatro Modu) -->
        <div style="display:grid;grid-template-columns:1fr;gap:24px;margin-top:24px;">
            
            <!-- 1. ANA OYNATICI KARTI -->
            <div class="card" style="padding:0;overflow:hidden;border-radius:18px;border:1px solid var(--line);box-shadow:0 10px 30px rgba(0,0,0,0.06);background:#1a1412;">
                <!-- YouTube IFrame Player Kapsayıcısı (16:9 Aspect Ratio) -->
                <div style="position:relative;width:100%;aspect-ratio:16/9;background:#000;">
                    <div id="ytplayer" style="position:absolute;top:0;left:0;width:100%;height:100%;"></div>
                </div>

                <!-- Oynatıcı Kontrol ve Bilgi Çubuğu -->
                <div style="padding:18px 24px;background:var(--cream);border-top:1px solid var(--line);">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:12px;">
                        <!-- Oynatılan Video Bilgisi -->
                        <div style="min-width:0;flex:1;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                <span style="background:var(--rust);color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;letter-spacing:0.5px;">
                                    <span id="playerBadge">ŞİMDİ OYNATILIYOR</span>
                                </span>
                                <span class="muted" style="font-size:12px;font-weight:600;" id="playerCounter">
                                    Video 1 / {{ $videos->count() }}
                                </span>
                            </div>
                            <h2 id="currentTitle" style="font:20px 'DM Serif Display',serif;margin:0 0 4px;color:var(--wine);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $videos->first()->title }}
                            </h2>
                            <div class="muted" style="font-size:13px;display:flex;align-items:center;gap:6px;">
                                <span>📺 Kanal: <strong id="currentChannel" style="color:var(--ink);">{{ $videos->first()->channel?->title ?? 'Bilinmeyen Kanal' }}</strong></span>
                                <span>•</span>
                                <a id="currentYtLink" href="https://www.youtube.com/watch?v={{ $videos->first()->youtube_video_id }}" target="_blank" rel="noopener noreferrer" style="color:var(--rust);text-decoration:underline;font-weight:600;">
                                    YouTube'da Aç ↗
                                </a>
                            </div>
                        </div>

                        <!-- Sıralı Oynatma Kontrolleri -->
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <button type="button" id="prevBtn" class="btn secondary" style="padding:10px 14px;font-size:13px;display:inline-flex;align-items:center;gap:4px;" title="Önceki Video">
                                <span>⏮ Önceki</span>
                            </button>
                            <button type="button" id="nextBtn" class="btn" style="padding:10px 16px;font-size:13px;display:inline-flex;align-items:center;gap:6px;" title="Sonraki Video">
                                <span>Sonraki ⏭</span>
                            </button>
                            <button type="button" id="autoAdvanceToggle" class="btn secondary" style="padding:10px 14px;font-size:13px;background:#e8f5e9;color:#2e7d32;border-color:#c8e6c9;display:inline-flex;align-items:center;gap:6px;" title="Sırayla Otomatik Geçiş">
                                <span>🔁 Sırayla Geçiş: Açık</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. OYNATMA SIRASI / LİSTESİ (QUEUE) -->
            <div class="card" style="padding:22px 26px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                    <div>
                        <div class="eyebrow">Oynatma Sırası</div>
                        <h3 style="font:20px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">Sıradaki Videolar ({{ $videos->count() }} İçerik)</h3>
                    </div>
                    <span class="muted" style="font-size:12px;">İstediğiniz videoya tıklayarak anında geçiş yapabilirsiniz</span>
                </div>

                <div id="playlistContainer" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));gap:14px;">
                    @foreach($videos as $idx => $v)
                    <div class="playlist-item"
                        data-index="{{ $idx }}"
                        data-video-id="{{ $v->youtube_video_id }}"
                        data-title="{{ $v->title }}"
                        data-channel="{{ $v->channel?->title ?? 'YouTube Kanalı' }}"
                        style="cursor:pointer;border-radius:12px;border:2px solid {{ $idx === 0 ? 'var(--rust)' : 'var(--line)' }};background:{{ $idx === 0 ? '#fff8f5' : 'var(--cream)' }};overflow:hidden;transition:all .15s ease;display:flex;flex-direction:column;">
                        
                        <div style="position:relative;width:100%;aspect-ratio:16/9;background:#1a1412;overflow:hidden;">
                            @if($v->thumbnail_url)
                                <img src="{{ $v->thumbnail_url }}" alt="{{ $v->title }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;">
                            @else
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;">▶</div>
                            @endif
                            
                            <span class="item-badge" style="position:absolute;top:8px;left:8px;background:{{ $idx === 0 ? 'var(--rust)' : 'rgba(32,26,23,0.75)' }};color:#fff;font-size:11px;font-weight:700;padding:3px 7px;border-radius:5px;">
                                {{ $idx === 0 ? '▶ Şimdi' : '#' . ($idx + 1) }}
                            </span>
                        </div>

                        <div style="padding:10px 12px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                            <div style="font-weight:600;font-size:13px;line-height:1.4;color:var(--wine);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:6px;" title="{{ $v->title }}">
                                {{ $v->title }}
                            </div>
                            <div class="muted" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                📺 {{ $v->channel?->title ?? 'Kanal' }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Topluluk Bilgilendirme Notu -->
            <div class="card" style="background:#f7efe5;border:1px solid #e7d8ca;padding:16px 20px;font-size:13px;color:#614c40;line-height:1.6;display:flex;align-items:center;gap:12px;">
                <span style="font-size:20px;">🛡️</span>
                <span>
                    <strong>Organik Keşif İlkesi:</strong> Video izlemek zorunlu bir görev, takip şartı veya platform puanı kazandırmaz. Amaç, bağımsız içerik üreticilerini keşfetmek ve doğal etkileşim kurmaktır.
                </span>
            </div>

        </div>

        <!-- YouTube IFrame Player API Entegrasyonu & Sıralı Oynatma Motoru -->
        <script>
        (function() {
            // PHP'den güvenli şekilde aktarılan video dizisi
            const playlist = @json($videos->map(fn($v) => [
                'id'       => $v->youtube_video_id,
                'title'    => $v->title,
                'channel'  => $v->channel?->title ?? 'YouTube Kanalı',
            ])->values());

            let currentIndex = 0;
            let autoAdvance = true;
            let ytPlayer = null;

            const titleEl = document.getElementById('currentTitle');
            const channelEl = document.getElementById('currentChannel');
            const ytLinkEl = document.getElementById('currentYtLink');
            const counterEl = document.getElementById('playerCounter');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const autoBtn = document.getElementById('autoAdvanceToggle');
            const items = document.querySelectorAll('.playlist-item');

            // 1. YouTube IFrame API Script Yüklemesi
            if (!window.YT) {
                const tag = document.createElement('script');
                tag.src = "https://www.youtube.com/iframe_api";
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }

            window.onYouTubeIframeAPIReady = function() {
                if (!playlist || playlist.length === 0) return;
                ytPlayer = new YT.Player('ytplayer', {
                    videoId: playlist[0].id,
                    playerVars: {
                        'autoplay': 1,
                        'rel': 0,
                        'modestbranding': 1,
                        'playsinline': 1
                    },
                    events: {
                        'onReady': onPlayerReady,
                        'onStateChange': onPlayerStateChange
                    }
                });
            };

            // Eğer API daha önce yüklendiyse doğrudan başlat
            if (window.YT && window.YT.Player) {
                window.onYouTubeIframeAPIReady();
            }

            function onPlayerReady(event) {
                updateUI(currentIndex);
                // Kullanıcı etkileşimiyle otomatik başlatmayı dene
                event.target.playVideo();
            }

            // 2. Video Bittiğinde Sıradakine Geç (Kritik İster!)
            function onPlayerStateChange(event) {
                // YT.PlayerState.ENDED === 0
                if (event.data === 0) {
                    if (autoAdvance) {
                        playNext();
                    }
                }
            }

            function playVideoAtIndex(idx) {
                if (idx < 0 || idx >= playlist.length) return;
                currentIndex = idx;
                const video = playlist[currentIndex];

                if (ytPlayer && ytPlayer.loadVideoById) {
                    ytPlayer.loadVideoById(video.id);
                }

                updateUI(currentIndex);
            }

            function playNext() {
                if (currentIndex + 1 < playlist.length) {
                    playVideoAtIndex(currentIndex + 1);
                } else {
                    // Liste sonuna gelindiyse başa dön
                    playVideoAtIndex(0);
                }
            }

            function playPrev() {
                if (currentIndex > 0) {
                    playVideoAtIndex(currentIndex - 1);
                } else {
                    playVideoAtIndex(playlist.length - 1);
                }
            }

            function updateUI(idx) {
                const video = playlist[idx];
                if (!video) return;

                if (titleEl) titleEl.textContent = video.title;
                if (channelEl) channelEl.textContent = video.channel;
                if (ytLinkEl) ytLinkEl.href = 'https://www.youtube.com/watch?v=' + encodeURIComponent(video.id);
                if (counterEl) counterEl.textContent = 'Video ' + (idx + 1) + ' / ' + playlist.length;

                // Liste kartlarını güncelle
                items.forEach((item, i) => {
                    const badge = item.querySelector('.item-badge');
                    if (i === idx) {
                        item.style.borderColor = 'var(--rust)';
                        item.style.background = '#fff8f5';
                        item.style.boxShadow = '0 0 0 3px rgba(173,101,72,0.18)';
                        if (badge) {
                            badge.textContent = '▶ Şimdi';
                            badge.style.background = 'var(--rust)';
                        }
                    } else {
                        item.style.borderColor = 'var(--line)';
                        item.style.background = 'var(--cream)';
                        item.style.boxShadow = 'none';
                        if (badge) {
                            badge.textContent = '#' + (i + 1);
                            badge.style.background = 'rgba(32,26,23,0.75)';
                        }
                    }
                });
            }

            // Buton Olay Dinleyicileri
            if (nextBtn) nextBtn.addEventListener('click', playNext);
            if (prevBtn) prevBtn.addEventListener('click', playPrev);

            if (autoBtn) {
                autoBtn.addEventListener('click', function() {
                    autoAdvance = !autoAdvance;
                    if (autoAdvance) {
                        autoBtn.innerHTML = '<span>🔁 Sırayla Geçiş: Açık</span>';
                        autoBtn.style.background = '#e8f5e9';
                        autoBtn.style.color = '#2e7d32';
                        autoBtn.style.borderColor = '#c8e6c9';
                    } else {
                        autoBtn.innerHTML = '<span>⏸ Sırayla Geçiş: Kapalı</span>';
                        autoBtn.style.background = '#fff3e0';
                        autoBtn.style.color = '#e65100';
                        autoBtn.style.borderColor = '#ffe0b2';
                    }
                });
            }

            // Oynatma listesi kartlarına tıklama olayı
            items.forEach((item) => {
                item.addEventListener('click', function() {
                    const idx = parseInt(this.getAttribute('data-index'), 10);
                    if (!isNaN(idx)) {
                        playVideoAtIndex(idx);
                    }
                });
            });
        })();
        </script>
    @endif
</div>
@endsection
