@extends('layouts.app')
@section('title', 'Genel Bakış & Profilim')
@section('content')
<div style="max-width:1040px;margin:0 auto;">

    <!-- 1. BİRLEŞTİRİLMİŞ ÜRETİCİ & KANAL PROFİL KARTI (TEK PARÇA) -->
    <div class="card" style="padding:0;overflow:hidden;border:1px solid var(--line);box-shadow:0 8px 30px rgba(0,0,0,0.04);margin-bottom:24px;">
        <!-- Banner Arka Planı -->
        <div style="height:140px;background:linear-gradient(135deg,#56362f 0%,#2e1b17 50%,#7a4537 100%);position:relative;">
            <div style="position:absolute;right:20px;bottom:12px;color:rgba(255,255,255,0.7);font-size:12px;font-weight:500;">
                aiajan.com.tr topluluğu
            </div>
        </div>

        <!-- Profil Bilgileri & Aksiyon Butonları -->
        <div style="padding:0 28px 24px;position:relative;">
            <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-top:-50px;margin-bottom:16px;">
                <!-- Avatar (Kanal veya Profil Görseli) -->
                <div style="position:relative;">
                    @if($channel?->thumbnail_url)
                        <img src="{{ $channel->thumbnail_url }}" alt="{{ $channel->title }}" style="width:105px;height:105px;border-radius:50%;object-fit:cover;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--sand);">
                    @elseif($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width:105px;height:105px;border-radius:50%;object-fit:cover;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--sand);">
                    @else
                        <div style="width:105px;height:105px;border-radius:50%;border:4px solid var(--cream);box-shadow:0 6px 18px rgba(0,0,0,0.15);background:var(--wine);color:#fff;display:flex;align-items:center;justify-content:center;font:700 36px 'DM Serif Display';">
                            {{ strtoupper(mb_substr($channel?->title ?? $user->name, 0, 1)) }}
                        </div>
                    @endif
                    @if($channel)
                        <span title="YouTube Kanalı Doğrulandı ve Aktif" style="position:absolute;bottom:4px;right:4px;background:#e52d27;color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;border:2px solid var(--cream);box-shadow:0 2px 6px rgba(0,0,0,0.2);">▶</span>
                    @endif
                </div>

                <!-- Aksiyon Butonları (Tek Parça, Tekrarsız) -->
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:4px;flex-wrap:wrap;">
                    @if($channel)
                        <a href="{{ route('videos.showcase') }}" class="btn" style="padding:9px 16px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                            <span>✨ Video Vitrinim</span>
                        </a>
                        <a href="{{ route('channels.select') }}" class="btn secondary" style="padding:9px 14px;font-size:13px;">
                            ⚙️ Kanalı Değiştir
                        </a>
                        <a href="https://www.youtube.com/{{ $channel->custom_url ? ltrim($channel->custom_url, '/') : 'channel/' . $channel->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" class="btn secondary" style="padding:9px 14px;font-size:13px;display:inline-flex;gap:5px;align-items:center;">
                            <span>YouTube'da Aç</span>
                            <span>↗</span>
                        </a>
                    @else
                        <a href="{{ route('channels.select') }}" class="btn" style="padding:10px 18px;font-size:13px;">
                            ➕ YouTube Kanalı Bağla
                        </a>
                    @endif
                </div>
            </div>

            <!-- Başlık, Handle ve Bilgiler -->
            <div>
                @if($channel)
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#e52d27;">Temsil Edilen Aktif YouTube Kanalınız</span>
                        <span style="padding:2px 8px;border-radius:4px;background:#e6f4ea;color:#137333;font-size:11px;font-weight:600;">Aktif</span>
                    </div>
                    <h1 style="font:32px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $channel->title }}</h1>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;color:var(--muted);font-size:14px;margin-bottom:14px;">
                        <span style="font-weight:600;color:var(--rust);">{{ $channel->custom_url ? ('@' . ltrim($channel->custom_url, '@')) : ('@' . $user->username) }}</span>
                        <span>·</span>
                        <span>Hesap Sahibi: <strong>{{ $user->name }}</strong> ({{ '@' . $user->username }})</span>
                        <span>·</span>
                        <span>Bağlantı: {{ $activeLink?->started_at ? $activeLink->started_at->translatedFormat('d M Y') : ($user->created_at?->translatedFormat('d M Y') ?? '2026') }}</span>
                    </div>
                @else
                    <h1 style="font:32px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $user->name }}</h1>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;color:var(--muted);font-size:14px;margin-bottom:14px;">
                        <span style="font-weight:600;color:var(--rust);">{{ '@' . $user->username }}</span>
                        <span>·</span>
                        <span>Katılım: {{ $user->created_at?->translatedFormat('F Y') ?? '2026' }}</span>
                    </div>
                @endif

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
                        <span style="padding:4px 10px;border-radius:6px;background:#fef7e0;color:#9b5a00;font-size:12px;font-weight:600;">⚠️ YouTube Kanalı Bekleniyor</span>
                    @endif
                </div>

                @if(!$channel)
                    <div style="margin-top:16px;padding:12px 16px;border-radius:8px;background:#fffdfa;border:1px dashed var(--rust);font-size:13px;color:var(--muted);">
                        💡 Topluluk havuzunda yer almak ve video vitrininizi oluşturmak için lütfen yukarıdaki <strong>“➕ YouTube Kanalı Bağla”</strong> butonuyla kanalınızı bağlayın.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 2. DÖRT TEMEL TOPLULUK METRİĞİ -->
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

    <!-- 3. GÜNLÜK KEŞİF VE VİTRİN DURUM KARTLARI -->
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

    <!-- 4. ŞEFFAFLIK VE ORGANİK TOPLULUK İLKESİ -->
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
