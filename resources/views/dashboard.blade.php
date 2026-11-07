@extends('layouts.app')
@section('title', 'Genel Bakış & Profilim')
@section('content')
<div style="max-width:1040px;margin:0 auto;">

    <!-- 1. ÜST PROFİL BANNER & KİMLİK KARTI -->
    <div class="card" style="padding:0;overflow:hidden;border:1px solid var(--line);box-shadow:0 8px 30px rgba(0,0,0,0.04);margin-bottom:24px;">
        <!-- Banner Arka Planı -->
        <div style="height:140px;background:linear-gradient(135deg,#56362f 0%,#2e1b17 50%,#7a4537 100%);position:relative;">
            <div style="position:absolute;right:20px;bottom:12px;color:rgba(255,255,255,0.7);font-size:12px;font-weight:500;">
                aiajan.com.tr topluluğu
            </div>
        </div>

        <!-- Profil Bilgileri & Avatar -->
        <div style="padding:0 28px 24px;position:relative;">
            <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-top:-50px;margin-bottom:16px;">
                <!-- Avatar -->
                <div style="position:relative;">
                    @if($channel?->thumbnail_url)
                        <img src="{{ $channel->thumbnail_url }}" alt="{{ $user->name }}" style="width:105px;height:105px;border-radius:50%;object-fit:cover;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--sand);">
                    @elseif($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width:105px;height:105px;border-radius:50%;object-fit:cover;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--sand);">
                    @else
                        <div style="width:105px;height:105px;border-radius:50%;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--wine);color:#fff;display:flex;align-items:center;justify-content:center;font:700 36px 'DM Serif Display';">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    @if($channel)
                        <span title="YouTube Kanalı Doğrulandı" style="position:absolute;bottom:4px;right:4px;background:#e52d27;color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;border:2px solid var(--cream);box-shadow:0 2px 6px rgba(0,0,0,0.2);">▶</span>
                    @endif
                </div>

                <!-- Aksiyon Butonları (Yalnızca Vitrin ve eksikse Kanal Bağla) -->
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:4px;flex-wrap:wrap;">
                    @if(!$channel)
                        <a href="{{ route('channels.select') }}" class="btn" style="padding:9px 16px;font-size:13px;">
                            ➕ Kanal Bağla
                        </a>
                    @endif
                    <a href="{{ route('videos.showcase') }}" class="btn secondary" style="padding:9px 16px;font-size:13px;">
                        ✨ Video Vitrinim
                    </a>
                </div>
            </div>

            <!-- Kullanıcı Başlığı ve Rozetler -->
            <div>
                <h1 style="font:34px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $user->name }}</h1>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:var(--muted);font-size:14px;margin-bottom:14px;">
                    <span style="font-weight:600;color:var(--rust);">{{ '@' . $user->username }}</span>
                    <span>·</span>
                    <span>Katılım: {{ $user->created_at?->translatedFormat('F Y') ?? '2026' }}</span>
                </div>

                <!-- Rozetler -->
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @if($user->role === 'admin')
                        <span style="padding:4px 10px;border-radius:6px;background:#fce8e6;color:#c5221f;font-size:12px;font-weight:600;">👑 Platform Yöneticisi</span>
                    @else
                        <span style="padding:4px 10px;border-radius:6px;background:var(--sand);color:var(--ink);font-size:12px;font-weight:600;">🎬 İçerik Üreticisi</span>
                    @endif

                    @if($isSupporter)
                        <span style="padding:4px 10px;border-radius:6px;background:#fef7e0;color:#b06000;font-size:12px;font-weight:600;">⭐ Aktif Destekçi</span>
                    @endif

                    @if($channel)
                        <span style="padding:4px 10px;border-radius:6px;background:#e6f4ea;color:#137333;font-size:12px;font-weight:600;">✓ YouTube Bağlı</span>
                    @else
                        <span style="padding:4px 10px;border-radius:6px;background:#fef7e0;color:#9b5a00;font-size:12px;font-weight:600;">⚠️ Kanal Bekleniyor</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 2. TEMSİL EDİLEN AKTİF YOUTUBE KANALI -->
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
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                            <span style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#e52d27;">Temsil Edilen Aktif YouTube Kanalınız</span>
                            <span style="padding:2px 8px;border-radius:4px;background:#e6f4ea;color:#137333;font-size:11px;font-weight:600;">Aktif</span>
                        </div>
                        <h2 style="font:26px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $channel->title }}</h2>
                        <div style="font-size:13px;color:var(--muted);">
                            {{ $channel->custom_url ? $channel->custom_url : ('ID: ' . $channel->youtube_channel_id) }}
                            @if($activeLink?->started_at)
                                · Bağlantı: {{ $activeLink->started_at->translatedFormat('d M Y') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <a href="https://www.youtube.com/{{ $channel->custom_url ? ltrim($channel->custom_url, '/') : 'channel/' . $channel->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" class="btn secondary" style="padding:10px 16px;font-size:13px;display:inline-flex;gap:6px;align-items:center;">
                        <span>YouTube'da Aç</span>
                        <span>↗</span>
                    </a>
                    <a href="{{ route('channels.select') }}" class="btn secondary" style="padding:10px 16px;font-size:13px;">
                        Kanalı Değiştir
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Kanal Bağlı Değilse Net Uyarı Kartı -->
        <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#fffdfa 0%,#fdf5ec 100%);border:2px dashed var(--rust);text-align:center;padding:36px 24px;">
            <div style="font-size:44px;margin-bottom:12px;">📺</div>
            <h2 style="font:26px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Henüz YouTube Kanalınızı Bağlamadınız</h2>
            <p class="muted" style="max-width:520px;margin:0 auto 20px;line-height:1.6;font-size:14px;">
                Topluluk havuzunda yer almak, video vitrininizi oluşturmak ve üretici dayanışmasına katılmak için kanalınızı bağlayın.
            </p>
            <a href="{{ route('channels.select') }}" class="btn" style="padding:12px 26px;font-size:14px;">
                <span>➕ YouTube Kanalını Şimdi Bağla</span>
            </a>
        </div>
    @endif

    <!-- 3. DÖRT TEMEL TOPLULUK METRİĞİ (GÖRSELDEKİ KARTLAR) -->
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:24px;">
        <div class="card" style="padding:20px;">
            <div class="eyebrow">PLATFORM TAKİBİ</div>
            <div class="stat" style="color:var(--wine);">{{ $followsCount }}</div>
            <div class="muted">Takip ettiğin üretici</div>
        </div>

        <div class="card" style="padding:20px;">
            <div class="eyebrow">TOPLULUK İLGİSİ</div>
            <div class="stat" style="color:var(--rust);">{{ $followersCount }}</div>
            <div class="muted">Seni takip eden üretici</div>
        </div>

        <div class="card" style="padding:20px;">
            <div class="eyebrow">KEŞİF HAVUZU</div>
            <div style="font:24px 'DM Serif Display';margin:10px 0 6px;color:var(--ink);">
                {{ $channel ? 'Aktif Havuz' : 'Beklemede' }}
            </div>
            <div class="muted">{{ $channel ? 'Üretici listesinde görünür' : 'Kanal bağlantısı bekleniyor' }}</div>
        </div>

        <div class="card" style="padding:20px;">
            <div class="eyebrow">DESTEKÇİ STATÜSÜ</div>
            <div style="font:24px 'DM Serif Display';margin:10px 0 6px;color:var(--ink);">
                {{ $isSupporter ? 'Aktif Destekçi' : 'Standart Üye' }}
            </div>
            <div class="muted">{{ $isSupporter ? 'Öncelikli havuzda listelenir' : 'Gelişmiş destek havuzu' }}</div>
        </div>
    </div>

    <!-- 4. GÜNLÜK KEŞİF VE VİTRİN DURUM KARTLARI -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:28px;">
        <div class="card" style="padding:20px;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div class="eyebrow">Bugünkü Keşif Kotası</div>
                <div style="font:28px 'DM Serif Display';margin:6px 0 2px;color:var(--wine);">
                    {{ $followsToday }} / 6
                </div>
                <div class="muted" style="font-size:12px;">Günlük platform takip hakkı</div>
            </div>
            <a href="{{ route('discovery.users', 'normal') }}" class="btn secondary" style="padding:9px 14px;font-size:12px;">
                Keşfe Başla →
            </a>
        </div>

        <div class="card" style="padding:20px;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div class="eyebrow">Vitrin Durumu</div>
                <div style="font:28px 'DM Serif Display';margin:6px 0 2px;color:var(--rust);">
                    {{ $showcaseVideos->count() }} / 3 Video
                </div>
                <div class="muted" style="font-size:12px;">Keşifte öne çıkan içerik</div>
            </div>
            <a href="{{ route('videos.showcase') }}" class="btn secondary" style="padding:9px 14px;font-size:12px;">
                Vitrini Düzenle ↗
            </a>
        </div>
    </div>

    <!-- 5. VİTRİNDEKİ VİDEOLARIM BÖLÜMÜ (YALNIZCA SEÇİLEN 3 VİDEO) -->
    <section class="card" style="margin-bottom:28px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
            <div>
                <div class="eyebrow" style="color:var(--rust);">Öne Çıkan İçerikler</div>
                <h2 style="font:24px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">Vitrindeki Videolarım</h2>
            </div>
            <a href="{{ route('videos.showcase') }}" class="btn" style="padding:8px 16px;font-size:13px;">
                ⚙️ Vitrini Yönet
            </a>
        </div>

        @if($showcaseVideos->isEmpty())
            <div style="text-align:center;padding:32px 16px;background:#fffdfa;border:2px dashed #e7d8ca;border-radius:12px;">
                <div style="font-size:36px;margin-bottom:8px;">✨</div>
                <div style="font-weight:700;font-size:16px;color:var(--wine);margin-bottom:4px;">Henüz Vitrininizde Video Bulunmuyor</div>
                <p class="muted" style="max-width:480px;margin:0 auto 16px;font-size:13px;line-height:1.5;">
                    Topluluk keşif havuzunda diğer üreticilerin izlemesi için en fazla 3 videonuzu vitrininize ekleyin.
                </p>
                <a href="{{ route('videos.showcase') }}" class="btn secondary" style="padding:10px 18px;font-size:13px;">
                    ＋ Vitrine Video Ekle
                </a>
            </div>
        @else
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
                            ⭐ Vitrinde Yayında
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
                            <span class="muted" style="font-size:11px;">Slot {{ $idx + 1 }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- 6. DİĞER KAYITLI KANALLAR (EĞER VARSA) -->
    @if(isset($otherChannels) && $otherChannels->isNotEmpty())
        <div class="card" style="margin-bottom:28px;padding:22px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <div class="eyebrow">Alternatif Kanallarınız</div>
                <span style="font-size:12px;color:var(--muted);background:var(--sand);padding:2px 8px;border-radius:6px;">{{ $otherChannels->count() }} alternatif</span>
            </div>
            <p class="muted" style="font-size:13px;margin:0 0 14px;">
                Temsil etmek istediğiniz kanalı aşağıdan seçerek tek tıkla aktif edebilirsiniz.
            </p>

            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($otherChannels as $oc)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;border-radius:10px;background:#fffdfa;border:1px solid var(--line);">
                        <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                            @if($oc->thumbnail_url)
                                <img src="{{ $oc->thumbnail_url }}" alt="{{ $oc->title }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                            @else
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0;">▶</div>
                            @endif
                            <div style="min-width:0;flex:1;">
                                <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $oc->title }}</div>
                                <div style="font-size:11px;color:var(--muted);">{{ $oc->custom_url ?? $oc->youtube_channel_id }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('channels.store') }}" style="margin:0;flex-shrink:0;">
                            @csrf
                            <input type="hidden" name="channel_id" value="{{ $oc->youtube_channel_id }}">
                            <button type="submit" class="btn secondary" style="padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--rust);color:var(--rust);">
                                Bu Kanala Geç
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 7. ŞEFFAFLIK VE ORGANİK TOPLULUK İLKESİ -->
    <div class="card" style="background:#f7efe5;border:1px solid #e7d8ca;padding:18px 22px;">
        <div style="display:flex;gap:12px;align-items:flex-start;">
            <span style="font-size:22px;line-height:1;">🛡️</span>
            <div style="font-size:13px;color:#614c40;line-height:1.6;">
                <strong>Şeffaf ve Organik Topluluk İlkesi:</strong> YouTube Keşif profilinizde abone sayıları, izlenme baskısı veya yapay yarışlar sergilenmez. Amaç samimi üretici dayanışması ve birbirini keşfetmektir.
            </div>
        </div>
    </div>

</div>
@endsection
