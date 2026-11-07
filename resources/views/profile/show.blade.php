@extends('layouts.app')
@section('title', $profile->name . ' (@' . $profile->username . ')')

@section('content')
<div style="max-width:960px;margin:0 auto;">

    @if(session('status'))
        <div class="alert success" style="margin-bottom:20px;">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert" style="margin-bottom:20px;">{{ $errors->first() }}</div>
    @endif

    <!-- 1. Üst Profil Banner & Kimlik Kartı -->
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

                <!-- Aksiyon Butonları -->
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:4px;flex-wrap:wrap;">
                    @if($isMe)
                        <a href="{{ route('channels.select') }}" class="btn secondary" style="padding:9px 16px;font-size:13px;">
                            {{ $channel ? '⚙️ Kanalı Değiştir' : '➕ Kanal Bağla' }}
                        </a>
                        <a href="{{ route('videos.showcase') }}" class="btn secondary" style="padding:9px 16px;font-size:13px;">
                            ✨ Vitrinim
                        </a>
                        <form method="post" action="{{ route('logout') }}" style="display:inline;">
                            @csrf
                            <button class="btn secondary" type="submit" style="padding:9px 14px;font-size:13px;background:transparent;border:1px solid var(--line);">Çıkış</button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Kullanıcı Başlığı ve Rozetler -->
            <div>
                <h1 style="font:34px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $profile->name }}</h1>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:var(--muted);font-size:14px;margin-bottom:14px;">
                    <span style="font-weight:600;color:var(--rust);">{{ '@' . $profile->username }}</span>
                    <span>·</span>
                    <span>Katılım: {{ $profile->created_at?->translatedFormat('F Y') ?? '2026' }}</span>
                </div>

                <!-- Rozetler -->
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
                    @else
                        <span style="padding:4px 10px;border-radius:6px;background:#fef7e0;color:#9b5a00;font-size:12px;font-weight:600;">⚠️ Kanal Bekleniyor</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Temsil Edilen Aktif YouTube Kanalı -->
    @if($channel)
        <div class="card" style="margin-bottom:24px;border-left:5px solid #e52d27;background:var(--cream);">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div style="display:flex;align-items:center;gap:16px;">
                    @if($channel->thumbnail_url)
                        <img src="{{ $channel->thumbnail_url }}" alt="{{ $channel->title }}" style="width:68px;height:68px;border-radius:50%;object-fit:cover;border:2px solid var(--line);box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    @else
                        <div style="width:68px;height:68px;border-radius:50%;background:var(--wine);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;">▶</div>
                    @endif
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <span style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#e52d27;">Aktif YouTube Kanalınız</span>
                            <span style="padding:2px 8px;border-radius:4px;background:#e6f4ea;color:#137333;font-size:11px;font-weight:600;">Temsil Ediliyor</span>
                        </div>
                        <h2 style="font:28px 'DM Serif Display',serif;margin:0 0 4px;color:var(--ink);">{{ $channel->title }}</h2>
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
                    @if($isMe)
                        <a href="{{ route('channels.select') }}" class="btn" style="padding:10px 18px;font-size:13px;background:var(--wine);">
                            Kanal Değiştir
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @else
        <!-- Kanal Bağlı Değilse Tek ve Net Bir Eylem Çağrısı -->
        <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#fffdfa 0%,#fdf5ec 100%);border:2px dashed var(--rust);text-align:center;padding:40px 24px;">
            <div style="font-size:44px;margin-bottom:12px;">📺</div>
            <h2 style="font:28px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Henüz YouTube Kanalınızı Bağlamadınız</h2>
            <p class="muted" style="max-width:540px;margin:0 auto 20px;line-height:1.6;font-size:14px;">
                Topluluk havuzunda yer almak, videolarınızı sergilemek ve diğer üreticilerle dayanışma kurmak için kanalınızı bağlayın.
            </p>
            <a href="{{ route('channels.select') }}" class="btn" style="padding:13px 28px;font-size:15px;display:inline-flex;align-items:center;gap:8px;">
                <span>➕ YouTube Kanalını Şimdi Bağla</span>
            </a>
            <div style="font-size:12px;color:var(--muted);margin-top:12px;">
                Google ile otomatik getirebilir veya <code>@kullanıcıadı</code> ile anında bağlayabilirsiniz.
            </div>
        </div>
    @endif

    <!-- 3. Diğer Kayıtlı Kanallarınız (Yalnızca başka kanal varsa görünür - TEKRAR YOK) -->
    @if($isMe && isset($otherChannels) && $otherChannels->count() > 0)
        <div class="card" style="margin-bottom:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <div class="eyebrow" style="color:var(--wine);">Diğer Kanallarınız</div>
                <span style="font-size:12px;color:var(--muted);background:var(--sand);padding:2px 8px;border-radius:6px;">{{ $otherChannels->count() }} alternatif kanal</span>
            </div>
            <p class="muted" style="font-size:13px;margin:0 0 16px;">
                Platformda temsil etmek istediğiniz kanalı aşağıdan seçerek tek tıkla aktif edebilirsiniz.
            </p>

            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($otherChannels as $oc)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 16px;border-radius:10px;background:var(--cream);border:1px solid var(--line);transition:border-color 0.15s;">
                        <div style="display:flex;align-items:center;gap:14px;min-width:0;flex:1;">
                            @if($oc->thumbnail_url)
                                <img src="{{ $oc->thumbnail_url }}" alt="{{ $oc->title }}" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:1px solid var(--line);flex-shrink:0;">
                            @else
                                <div style="width:46px;height:46px;border-radius:50%;background:var(--sand);color:var(--wine);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;flex-shrink:0;">▶</div>
                            @endif
                            <div style="min-width:0;flex:1;">
                                <div style="font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--ink);">{{ $oc->title }}</div>
                                <div style="font-size:12px;color:var(--muted);margin-top:2px;">{{ $oc->custom_url ?? ('Kanal ID: ' . $oc->youtube_channel_id) }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('channels.store') }}" style="margin:0;flex-shrink:0;">
                            @csrf
                            <input type="hidden" name="channel_id" value="{{ $oc->youtube_channel_id }}">
                            <button type="submit" class="btn secondary" style="padding:8px 14px;font-size:12px;font-weight:600;white-space:nowrap;cursor:pointer;border:1px solid var(--rust);color:var(--rust);">
                                Bu Kanala Geç
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 4. Kanal Geçmişi (Yalnızca sonlandırılmış eski kanallar listelenir - TEKRAR YOK) -->
    @if(isset($pastLinks) && $pastLinks->count() > 0)
        <div class="card" style="margin-bottom:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <div class="eyebrow">Kanal Geçmişi</div>
                <span style="font-size:12px;color:var(--muted);">{{ $pastLinks->count() }} geçmiş bağlantı</span>
            </div>
            <p class="muted" style="font-size:13px;margin:0 0 16px;">
                Daha önce bu hesapta temsil edilmiş kanalların geçmişi.
            </p>

            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($pastLinks as $link)
                    @if($link->channel)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 16px;border-radius:10px;background:#faf6f0;border:1px solid var(--line);">
                            <div style="display:flex;align-items:center;gap:14px;min-width:0;flex:1;">
                                @if($link->channel->thumbnail_url)
                                    <img src="{{ $link->channel->thumbnail_url }}" alt="{{ $link->channel->title }}" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:1px solid var(--line);flex-shrink:0;opacity:0.85;">
                                @else
                                    <div style="width:42px;height:42px;border-radius:50%;background:var(--sand);color:var(--muted);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0;">▶</div>
                                @endif
                                <div style="min-width:0;flex:1;">
                                    <div style="font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--ink);">{{ $link->channel->title }}</div>
                                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">
                                        Bağlantı: {{ $link->started_at?->translatedFormat('d M Y') ?? '-' }}
                                        @if($link->ended_at)
                                            · Ayrıldı: {{ $link->ended_at->translatedFormat('d M Y') }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($isMe)
                                <form method="POST" action="{{ route('channels.store') }}" style="margin:0;flex-shrink:0;">
                                    @csrf
                                    <input type="hidden" name="channel_id" value="{{ $link->channel->youtube_channel_id }}">
                                    <button type="submit" class="btn secondary" style="padding:7px 13px;font-size:12px;font-weight:600;white-space:nowrap;cursor:pointer;">
                                        Tekrar Bağla
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- 5. İstatistikler & Topluluk Metrikleri Grid -->
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:24px;">
        <div class="card" style="padding:18px;">
            <div class="eyebrow">Platform Takibi</div>
            <div class="stat" style="color:var(--wine);">{{ $followsCount }}</div>
            <div class="muted">Takip ettiğin üretici</div>
        </div>

        <div class="card" style="padding:18px;">
            <div class="eyebrow">Topluluk İlgisi</div>
            <div class="stat" style="color:var(--rust);">{{ $followersCount }}</div>
            <div class="muted">Seni takip eden üretici</div>
        </div>

        <div class="card" style="padding:18px;">
            <div class="eyebrow">Keşif Havuzu</div>
            <div style="font:22px 'DM Serif Display';margin:12px 0 6px;color:var(--ink);">
                {{ $channel ? 'Aktif Havuz' : 'Beklemede' }}
            </div>
            <div class="muted">{{ $channel ? 'Üretici listesinde görünür' : 'Kanal bağlantısı bekleniyor' }}</div>
        </div>

        <div class="card" style="padding:18px;">
            <div class="eyebrow">Destekçi Statüsü</div>
            <div style="font:22px 'DM Serif Display';margin:12px 0 6px;color:var(--ink);">
                {{ $isSupporter ? 'Aktif Destekçi' : 'Standart Üye' }}
            </div>
            <div class="muted">{{ $isSupporter ? 'Öncelikli havuzda listelenir' : 'Gelişmiş destek havuzu' }}</div>
        </div>
    </div>

    <!-- 6. Topluluk İlkeleri & Şeffaflık Notu -->
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
