@extends('layouts.app')
@section('title', $profile->name . ' (@' . $profile->username . ') · YouTube Keşif')

@section('content')
<div style="max-width:960px;margin:0 auto;">

    <!-- Üst Profil Banner & Kimlik Kartı -->
    <div class="card" style="padding:0;overflow:hidden;border:1px solid var(--line);box-shadow:0 8px 30px rgba(0,0,0,0.04);margin-bottom:24px;">
        <div style="height:140px;background:linear-gradient(135deg,#56362f 0%,#2e1b17 50%,#7a4537 100%);position:relative;">
            <div style="position:absolute;right:20px;bottom:12px;color:rgba(255,255,255,0.7);font-size:12px;font-weight:500;">
                aiajan.com.tr topluluğu
            </div>
        </div>

        <div style="padding:0 28px 24px;position:relative;">
            <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-top:-50px;margin-bottom:16px;">
                <!-- Avatar -->
                <div style="position:relative;">
                    @if($channel?->thumbnail_url)
                        <img src="{{ $channel->thumbnail_url }}" alt="{{ $profile->name }}" style="width:105px;height:105px;border-radius:50%;object-fit:cover;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--sand);">
                    @elseif($profile->avatar_url)
                        <img src="{{ $profile->avatar_url }}" alt="{{ $profile->name }}" style="width:105px;height:105px;border-radius:50%;object-fit:cover;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--sand);">
                    @else
                        <div style="width:105px;height:105px;border-radius:50%;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--wine);color:#fff;display:flex;align-items:center;justify-content:center;font:700 36px 'DM Serif Display';">
                            {{ strtoupper(mb_substr($profile->name, 0, 1)) }}
                        </div>
                    @endif
                    @if($channel)
                        <span title="YouTube Kanalı Doğrulandı" style="position:absolute;bottom:4px;right:4px;background:#e52d27;color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;border:2px solid var(--cream);box-shadow:0 2px 6px rgba(0,0,0,0.2);">▶</span>
                    @endif
                </div>

                <!-- Aksiyon -->
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:4px;">
                    <a href="{{ route('dashboard') }}" class="btn secondary" style="padding:9px 16px;font-size:13px;">
                        ← Panoya Dön
                    </a>
                </div>
            </div>

            <!-- Kullanıcı Bilgisi ve Rozetler -->
            <div>
                <h1 style="font:34px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $profile->name }}</h1>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:var(--muted);font-size:14px;margin-bottom:14px;">
                    <span style="font-weight:600;color:var(--rust);">{{ '@' . $profile->username }}</span>
                    <span>·</span>
                    <span>Katılım: {{ $profile->created_at?->translatedFormat('F Y') ?? '2026' }}</span>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @if($profile->role === 'admin')
                        <span style="padding:4px 10px;border-radius:6px;background:#fce8e6;color:#c5221f;font-size:12px;font-weight:600;">👑 Platform Yöneticisi</span>
                    @else
                        <span style="padding:4px 10px;border-radius:6px;background:var(--sand);color:var(--ink);font-size:12px;font-weight:600;">🎬 İçerik Üreticisi</span>
                    @endif

                    @if($isSupporter)
                        <span style="padding:4px 10px;border-radius:6px;background:#fef7e0;color:#b06000;font-size:12px;font-weight:600;">⭐ Aktif Destekçi</span>
                    @endif

                    @if($channel)
                        <span style="padding:4px 10px;border-radius:6px;background:#e6f4ea;color:#137333;font-size:12px;font-weight:600;">✓ YouTube Bağlı</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Temsil Edilen YouTube Kanalı -->
    @if($channel)
        <div class="card" style="margin-bottom:24px;border-left:5px solid #e52d27;background:var(--cream);">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div style="display:flex;align-items:center;gap:16px;">
                    @if($channel->thumbnail_url)
                        <img src="{{ $channel->thumbnail_url }}" alt="{{ $channel->title }}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--line);box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    @else
                        <div style="width:64px;height:64px;border-radius:50%;background:var(--wine);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;">▶</div>
                    @endif
                    <div>
                        <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#e52d27;margin-bottom:3px;">
                            YouTube Kanalı
                        </div>
                        <h2 style="font:26px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $channel->title }}</h2>
                        <div style="font-size:13px;color:var(--muted);">
                            {{ $channel->custom_url ? $channel->custom_url : ('ID: ' . $channel->youtube_channel_id) }}
                        </div>
                    </div>
                </div>

                <a href="https://www.youtube.com/{{ $channel->custom_url ? ltrim($channel->custom_url, '/') : 'channel/' . $channel->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" class="btn secondary" style="padding:10px 16px;font-size:13px;display:inline-flex;gap:6px;align-items:center;">
                    <span>YouTube'da Aç</span>
                    <span>↗</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Üreticinin Vitrindeki Videoları -->
    @if(isset($showcaseVideos) && $showcaseVideos->isNotEmpty())
        <section class="card" style="margin-bottom:24px;padding:24px;">
            <div class="eyebrow" style="color:var(--rust);">Vitrin Videoları</div>
            <h2 style="font:24px 'DM Serif Display',serif;margin:2px 0 16px;color:var(--wine);">{{ $profile->name }} Tarafından Öne Çıkarılanlar</h2>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                @foreach($showcaseVideos as $idx => $sv)
                <div style="border-radius:12px;border:1px solid var(--line);background:var(--cream);overflow:hidden;display:flex;flex-direction:column;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                    <div style="position:relative;width:100%;aspect-ratio:16/9;background:#1a1412;">
                        @if($sv->thumbnail_url)
                            <img src="{{ $sv->thumbnail_url }}" alt="{{ $sv->title }}" style="width:100%;height:100%;object-fit:cover;display:block;">
                        @else
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;">▶</div>
                        @endif
                        <span style="position:absolute;top:8px;left:8px;background:var(--rust);color:#fff;font-size:10px;font-weight:700;padding:3px 7px;border-radius:5px;">
                            ⭐ Vitrin
                        </span>
                    </div>
                    <div style="padding:12px 14px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                        <h3 style="font-size:13px;font-weight:600;line-height:1.4;color:var(--wine);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0 0 10px;" title="{{ $sv->title }}">
                            {{ $sv->title }}
                        </h3>
                        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--line);padding-top:8px;">
                            <a href="https://www.youtube.com/watch?v={{ $sv->youtube_video_id }}" target="_blank" rel="noopener noreferrer" class="muted" style="font-size:12px;text-decoration:underline;">
                                YouTube'da İzle ↗
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Şeffaflık Notu -->
    <div class="card" style="background:#f7efe5;border:1px solid #e7d8ca;padding:16px 20px;">
        <div style="display:flex;gap:12px;align-items:center;">
            <span style="font-size:20px;">🛡️</span>
            <div style="font-size:13px;color:#614c40;line-height:1.5;">
                <strong>Topluluk İlkeleri:</strong> YouTube Keşif platformunda üreticiler organik dayanışma ile keşfedilir. Yapay yarışlar veya bot etkileşimleri barındırılmaz.
            </div>
        </div>
    </div>

</div>
@endsection
