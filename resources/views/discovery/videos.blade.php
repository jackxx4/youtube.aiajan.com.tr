@extends('layouts.app')
@section('title', $mode === 'player' ? 'Sıralı Video Oynatıcı' : 'Video Keşfi (10 Video Arasından 3 Seç)')
@section('content')
<div style="max-width:1120px;margin:0 auto;">

@if($mode === 'selection')
    <!-- ==================== ADIM 1: 10 VİDEO ARASINDAN 3 VİDEO SEÇİM MODU ==================== -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
        <div>
            <div class="eyebrow" style="color:var(--rust);">Topluluk Keşif Havuzu</div>
            <h1 class="h1" style="margin:4px 0 6px;">Videoları Keşfet</h1>
            <p class="lead" style="margin-bottom:0;">
                Aşağıdaki 10 video arasından izlemek istediğin <strong>3 videoyu seç</strong>. Seçtiğin videolar sırayla ve kesintisiz olarak oynatılacaktır.
            </p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn secondary" style="padding:10px 16px;font-size:13px;">
                ← Panoya Dön
            </a>
        </div>
    </div>

    @if($videos->isEmpty())
        <div class="card" style="margin-top:24px;padding:40px 24px;text-align:center;background:#fffdf9;border:2px dashed var(--line);">
            <div style="font-size:44px;margin-bottom:12px;">🎬</div>
            <h2 style="font:26px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Henüz Keşif Videosu Bulunmuyor</h2>
            <p class="muted" style="max-width:500px;margin:0 auto 20px;line-height:1.6;font-size:14px;">
                Topluluktaki içerik üreticileri vitrinlerine video ekledikçe burada listelenecektir.
            </p>
            <a href="{{ route('videos.showcase') }}" class="btn" style="padding:12px 24px;">
                <span>✨ Vitrinime Video Ekle</span>
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('discovery.videos.select') }}" id="videoSelectForm" style="margin-top:24px;">
            @csrf

            <!-- Seçim ve Aksiyon Çubuğu -->
            <div class="card" style="padding:16px 22px;background:#fbf7f2;border-left:4px solid var(--rust);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:22px;">🎬</span>
                    <div>
                        <strong id="counterText" style="font-size:15px;color:var(--wine);">0 / 3 Video Seçildi</strong>
                        <div class="muted" id="hintText" style="font-size:12px;">Sırayla oynatmak için lütfen 3 video seçin.</div>
                    </div>
                </div>
                <button type="submit" id="startPlayingBtn" class="btn" disabled style="padding:11px 22px;font-size:14px;display:inline-flex;align-items:center;gap:6px;">
                    <span>▶ Seçilen 3 Videoyu Sırayla Oynat</span>
                </button>
            </div>

            <!-- 10 Video Grid -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:18px;">
                @foreach($videos as $v)
                <label class="card video-choice-card" for="v_{{ $v->id }}" style="padding:0;overflow:hidden;border:2px solid var(--line);border-radius:14px;cursor:pointer;display:flex;flex-direction:column;transition:all .15s ease;position:relative;">
                    <div style="position:relative;width:100%;aspect-ratio:16/9;background:#1a1412;">
                        @if($v->thumbnail_url)
                            <img src="{{ $v->thumbnail_url }}" alt="{{ $v->title }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;">
                        @else
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;">▶</div>
                        @endif

                        <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.65);border-radius:6px;padding:4px;display:flex;align-items:center;justify-content:center;">
                            <input type="checkbox"
                                name="video_ids[]"
                                id="v_{{ $v->id }}"
                                value="{{ $v->id }}"
                                class="video-checkbox"
                                style="width:20px;height:20px;accent-color:var(--rust);cursor:pointer;margin:0;">
                        </div>
                    </div>

                    <div style="padding:14px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                        <h3 style="font-size:14px;font-weight:600;line-height:1.4;color:var(--wine);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0 0 8px;" title="{{ $v->title }}">
                            {{ $v->title }}
                        </h3>
                        <div class="muted" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            📺 {{ $v->channel?->title ?? 'YouTube Kanalı' }}
                        </div>
                    </div>
                </label>
                @endforeach
            </div>

            <!-- Alt Başlat Butonu -->
            <div style="margin-top:24px;text-align:center;">
                <button type="submit" id="startPlayingBtn2" class="btn" disabled style="padding:13px 32px;font-size:15px;">
                    ▶ Seçilen 3 Videoyu Sırayla Oynat
                </button>
            </div>
        </form>

        <script>
        (function() {
            const TARGET = 3;
            const boxes = Array.from(document.querySelectorAll('.video-checkbox'));
            const counterText = document.getElementById('counterText');
            const hintText = document.getElementById('hintText');
            const btns = [document.getElementById('startPlayingBtn'), document.getElementById('startPlayingBtn2')];

            function sync() {
                const checked = boxes.filter(b => b.checked);
                const count = checked.length;

                counterText.textContent = count + ' / ' + TARGET + ' Video Seçildi';

                if (count === TARGET) {
                    counterText.style.color = '#2e7d32';
                    hintText.textContent = 'Harika! 3 video seçildi, sırayla oynatabilirsiniz.';
                    btns.forEach(btn => { if (btn) { btn.disabled = false; } });
                } else {
                    counterText.style.color = 'var(--wine)';
                    hintText.textContent = (TARGET - count) + ' video daha seçmeniz bekleniyor.';
                    btns.forEach(btn => { if (btn) { btn.disabled = true; } });
                }

                boxes.forEach(b => {
                    const card = b.closest('.video-choice-card');
                    if (card) {
                        if (b.checked) {
                            card.style.borderColor = 'var(--rust)';
                            card.style.background = '#fff8f5';
                            card.style.boxShadow = '0 0 0 3px rgba(173,101,72,0.2)';
                        } else {
                            card.style.borderColor = 'var(--line)';
                            card.style.background = 'var(--cream)';
                            card.style.boxShadow = 'none';
                        }
                    }
                    if (!b.checked) {
                        b.disabled = (count >= TARGET);
                        if (card) {
                            card.style.opacity = (count >= TARGET) ? '0.5' : '1';
                        }
                    } else {
                        if (card) card.style.opacity = '1';
                    }
                });
            }

            boxes.forEach(b => b.addEventListener('change', sync));
            sync();
        })();
        </script>
    @endif

@else
    <!-- ==================== ADIM 2: SEÇİLEN 3 VİDEOYU SIRAYLA OYNATMA MODU ==================== -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
        <div>
            <div class="eyebrow" style="color:var(--rust);">Sıralı Oynatıcı Modu</div>
            <h1 class="h1" style="margin:4px 0 6px;">Seçtiğin 3 Video Sırayla Oynatılıyor</h1>
            <p class="lead" style="margin-bottom:0;">
                Seçtiğin 3 video sırayla ve otomatik geçişle oynatılmaktadır. Video tamamlandığında sıradakine geçer.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <form method="POST" action="{{ route('discovery.videos.reset') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn secondary" style="padding:10px 16px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                    <span>🔄 Yeni 10 Video Getir / Yeniden Seç</span>
                </button>
            </form>
            <a href="{{ route('dashboard') }}" class="btn" style="padding:10px 16px;font-size:13px;">
                <span>Panoya Dön</span>
            </a>
        </div>
    </div>

    <!-- Tiyatro Oynatıcı Kartı -->
    <div class="card" style="padding:0;overflow:hidden;border-radius:18px;border:1px solid var(--line);box-shadow:0 10px 30px rgba(0,0,0,0.06);background:#1a1412;margin-top:20px;">
        <!-- YouTube IFrame Player (16:9) -->
        <div style="position:relative;width:100%;aspect-ratio:16/9;background:#000;">
            <div id="ytplayer" style="position:absolute;top:0;left:0;width:100%;height:100%;"></div>
        </div>

        <!-- Kontrol ve Bilgi Çubuğu -->
        <div style="padding:18px 24px;background:var(--cream);border-top:1px solid var(--line);">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                <div style="min-width:0;flex:1;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span style="background:var(--rust);color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;letter-spacing:0.5px;">
                            ŞİMDİ OYNATILIYOR
                        </span>
                        <span class="muted" style="font-size:12px;font-weight:600;" id="playerCounter">
                            Video 1 / {{ count($playlist) }}
                        </span>
                    </div>
                    <h2 id="currentTitle" style="font:20px 'DM Serif Display',serif;margin:0 0 4px;color:var(--wine);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $playlist[0]['title'] ?? '' }}
                    </h2>
                    <div class="muted" style="font-size:13px;display:flex;align-items:center;gap:6px;">
                        <span>📺 Kanal: <strong id="currentChannel" style="color:var(--ink);">{{ $playlist[0]['channel'] ?? '' }}</strong></span>
                        <span>•</span>
                        <a id="currentYtLink" href="https://www.youtube.com/watch?v={{ $playlist[0]['id'] ?? '' }}" target="_blank" rel="noopener noreferrer" style="color:var(--rust);text-decoration:underline;font-weight:600;">
                            YouTube'da Aç ↗
                        </a>
                    </div>
                </div>

                <!-- Kontroller -->
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <button type="button" id="prevBtn" class="btn secondary" style="padding:10px 14px;font-size:13px;" title="Önceki Video">
                        <span>⏮ Önceki</span>
                    </button>
                    <button type="button" id="nextBtn" class="btn" style="padding:10px 16px;font-size:13px;" title="Sonraki Video">
                        <span>Sonraki ⏭</span>
                    </button>
                    <button type="button" id="autoAdvanceToggle" class="btn secondary" style="padding:10px 14px;font-size:13px;background:#e8f5e9;color:#2e7d32;border-color:#c8e6c9;" title="Sırayla Otomatik Geçiş">
                        <span>🔁 Otomatik Geçiş: Açık</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 Videoluk Oynatma Sırası -->
    <div class="card" style="margin-top:24px;padding:22px 24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div>
                <div class="eyebrow">Seçtiğin Oynatma Sırası</div>
                <h3 style="font:20px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">3 Video Listesi</h3>
            </div>
            <span class="muted" style="font-size:12px;">Videoya tıklayarak istediğinize geçebilirsiniz</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;">
            @foreach($selectedVideos as $idx => $sv)
            <div class="queue-item"
                data-index="{{ $idx }}"
                style="cursor:pointer;border-radius:12px;border:2px solid {{ $idx === 0 ? 'var(--rust)' : 'var(--line)' }};background:{{ $idx === 0 ? '#fff8f5' : 'var(--cream)' }};overflow:hidden;display:flex;flex-direction:column;transition:all .15s ease;">
                <div style="position:relative;width:100%;aspect-ratio:16/9;background:#1a1412;">
                    @if($sv->thumbnail_url)
                        <img src="{{ $sv->thumbnail_url }}" alt="{{ $sv->title }}" style="width:100%;height:100%;object-fit:cover;display:block;">
                    @else
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;">▶</div>
                    @endif
                    <span class="queue-badge" style="position:absolute;top:8px;left:8px;background:{{ $idx === 0 ? 'var(--rust)' : 'rgba(32,26,23,0.75)' }};color:#fff;font-size:11px;font-weight:700;padding:3px 7px;border-radius:5px;">
                        {{ $idx === 0 ? '▶ Şimdi' : '#' . ($idx + 1) }}
                    </span>
                </div>
                <div style="padding:12px 14px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                    <h4 style="font-size:13px;font-weight:600;line-height:1.4;color:var(--wine);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0 0 6px;" title="{{ $sv->title }}">
                        {{ $sv->title }}
                    </h4>
                    <div class="muted" style="font-size:11px;">
                        📺 {{ $sv->channel?->title ?? 'YouTube Kanalı' }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- YouTube IFrame Player Script -->
    <script>
    (function() {
        const playlist = {!! json_encode($playlist ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!};
        if (!playlist || playlist.length === 0) return;

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
        const items = document.querySelectorAll('.queue-item');

        if (!window.YT) {
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        window.onYouTubeIframeAPIReady = function() {
            ytPlayer = new YT.Player('ytplayer', {
                videoId: playlist[0].id,
                playerVars: {
                    'autoplay': 1,
                    'rel': 0,
                    'modestbranding': 1,
                    'playsinline': 1
                },
                events: {
                    'onReady': function(e) {
                        updateUI(currentIndex);
                        e.target.playVideo();
                    },
                    'onStateChange': function(e) {
                        // Video bittiğinde sıradaki videoya geç
                        if (e.data === 0 && autoAdvance) {
                            playNext();
                        }
                    }
                }
            });
        };

        if (window.YT && window.YT.Player) {
            window.onYouTubeIframeAPIReady();
        }

        function playVideoAtIndex(idx) {
            if (idx < 0 || idx >= playlist.length) return;
            currentIndex = idx;
            if (ytPlayer && ytPlayer.loadVideoById) {
                ytPlayer.loadVideoById(playlist[currentIndex].id);
            }
            updateUI(currentIndex);
        }

        function playNext() {
            if (currentIndex + 1 < playlist.length) {
                playVideoAtIndex(currentIndex + 1);
            } else {
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

            items.forEach((item, i) => {
                const badge = item.querySelector('.queue-badge');
                if (i === idx) {
                    item.style.borderColor = 'var(--rust)';
                    item.style.background = '#fff8f5';
                    item.style.boxShadow = '0 0 0 3px rgba(173,101,72,0.2)';
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

        if (nextBtn) nextBtn.addEventListener('click', playNext);
        if (prevBtn) prevBtn.addEventListener('click', playPrev);

        if (autoBtn) {
            autoBtn.addEventListener('click', function() {
                autoAdvance = !autoAdvance;
                if (autoAdvance) {
                    autoBtn.innerHTML = '<span>🔁 Otomatik Geçiş: Açık</span>';
                    autoBtn.style.background = '#e8f5e9';
                    autoBtn.style.color = '#2e7d32';
                    autoBtn.style.borderColor = '#c8e6c9';
                } else {
                    autoBtn.innerHTML = '<span>⏸ Otomatik Geçiş: Kapalı</span>';
                    autoBtn.style.background = '#fff3e0';
                    autoBtn.style.color = '#e65100';
                    autoBtn.style.borderColor = '#ffe0b2';
                }
            });
        }

        items.forEach(item => {
            item.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                if (!isNaN(idx)) playVideoAtIndex(idx);
            });
        });
    })();
    </script>
@endif

</div>
@endsection
