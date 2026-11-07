@extends('layouts.app')
@section('title', 'Yönetim Merkezi')
@section('content')
<div style="max-width:1160px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px;">
        <div>
            <div class="eyebrow" style="color:var(--rust);">Yönetici Kontrol Merkezi</div>
            <h1 class="h1" style="margin:4px 0 6px;">Platform Yönetimi</h1>
            <p class="lead" style="margin-bottom:0;">Üyeleri, YouTube kanallarını, vitrin durumunu ve gelen destek taleplerini canlı izleyin ve yönetin.</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <form method="POST" action="{{ route('admin.clear_cache') }}" onsubmit="return confirm('Sistem önbelleklerini temizlemek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="btn secondary" style="padding:10px 16px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                    <span>🧹 Önbelleği Temizle</span>
                </button>
            </form>
            <a href="{{ route('dashboard') }}" class="btn" style="padding:10px 16px;font-size:13px;">
                <span>Panoya Dön</span>
            </a>
        </div>
    </div>

    <!-- Sistem ve Canlı Altyapı Barı -->
    <div class="card" style="margin-top:20px;padding:14px 20px;background:#fbf7f2;border-left:4px solid var(--wine);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;">
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
            <span><strong>Sürüm:</strong> v{{ $systemInfo['version'] }} ({{ $systemInfo['release'] }})</span>
            <span>•</span>
            <span><strong>PHP:</strong> {{ $systemInfo['php_version'] }}</span>
            <span>•</span>
            <span><strong>Laravel:</strong> v{{ $systemInfo['laravel_ver'] }}</span>
            <span>•</span>
            <span><strong>Veritabanı:</strong> {{ strtoupper($systemInfo['db_driver']) }}</span>
            <span>•</span>
            <span><strong>Ortam:</strong> <span style="background:var(--sand);padding:2px 8px;border-radius:4px;font-weight:700;">{{ strtoupper($systemInfo['environment']) }}</span></span>
        </div>
        <span style="color:var(--rust);font-weight:600;">🛡️ MIT Seviyesi Sıfır-Güven Korumalı</span>
    </div>

    <!-- Metrik Kartları -->
    <div class="grid" style="grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:16px;margin-top:20px;">
        <div class="card">
            <div class="eyebrow">Toplam Üye</div>
            <div class="stat">{{ $totalUsers }}</div>
            <div class="muted" style="font-size:12px;">Bugün kaydolan: +{{ $usersToday }}</div>
        </div>

        <div class="card">
            <div class="eyebrow">Bağlı Kanal</div>
            <div class="stat">{{ $totalChannels }}</div>
            <div class="muted" style="font-size:12px;">Kayıtlı video: {{ $totalVideos }}</div>
        </div>

        <div class="card">
            <div class="eyebrow">Vitrindeki Videolar</div>
            <div class="stat" style="color:var(--rust);">{{ $totalShowcaseVideos }}</div>
            <div class="muted" style="font-size:12px;">Keşifte yayında olan</div>
        </div>

        <div class="card">
            <div class="eyebrow">Platform Takipleri</div>
            <div class="stat">{{ $totalFollows }}</div>
            <div class="muted" style="font-size:12px;">Bugün yapılan: +{{ $followsToday }}</div>
        </div>

        <div class="card">
            <div class="eyebrow">Aktif Destekçi</div>
            <div class="stat">{{ $activeSupporters }}</div>
            <div class="muted" style="font-size:12px;">Özel havuz üyeleri</div>
        </div>

        <div class="card">
            <div class="eyebrow">İletişim Talepleri</div>
            <div class="stat">{{ $totalMessages }}</div>
            <div class="muted" style="font-size:12px;">Gelen mesaj sayısı</div>
        </div>
    </div>

    <!-- TABLO 1: Son Kayıt Olan Kullanıcılar -->
    <section class="card" style="margin-top:32px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
            <div>
                <div class="eyebrow">Üye Yönetimi</div>
                <h2 style="font:22px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">Son Kayıt Olan Kullanıcılar (Son 15)</h2>
            </div>
            <span class="muted" style="font-size:12px;">Toplam {{ $totalUsers }} üyeden son 15 kişi listeleniyor</span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
                <thead>
                    <tr style="border-bottom:2px solid var(--line);color:var(--muted);font-weight:600;">
                        <th style="padding:10px 8px;">Kullanıcı</th>
                        <th style="padding:10px 8px;">E-Posta</th>
                        <th style="padding:10px 8px;">Bağlı Kanal</th>
                        <th style="padding:10px 8px;">Rol</th>
                        <th style="padding:10px 8px;">Durum</th>
                        <th style="padding:10px 8px;">Kayıt Tarihi</th>
                        <th style="padding:10px 8px;text-align:right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentUsers as $u)
                    <tr style="border-bottom:1px solid var(--line);">
                        <td style="padding:12px 8px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                @if($u->avatar_url)
                                    <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;">
                                @else
                                    <div style="width:34px;height:34px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);">
                                        {{ strtoupper(mb_substr($u->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <strong style="color:var(--wine);display:block;">{{ $u->name }}</strong>
                                    <span class="muted" style="font-size:11px;">{{ '@' . $u->username }}</span>
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px 8px;color:var(--ink);">{{ $u->email }}</td>
                        <td style="padding:12px 8px;">
                            @if($u->activeChannelLink?->channel)
                                <span style="color:var(--rust);font-weight:600;">📺 {{ $u->activeChannelLink->channel->title }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 8px;">
                            @if($u->role === 'admin')
                                <span style="background:var(--wine);color:#fff;padding:3px 8px;border-radius:12px;font-size:11px;font-weight:700;">YÖNETİCİ</span>
                            @else
                                <span style="background:var(--sand);color:var(--ink);padding:3px 8px;border-radius:12px;font-size:11px;">ÜYE</span>
                            @endif
                        </td>
                        <td style="padding:12px 8px;">
                            @if($u->status === 'active')
                                <span style="color:#2d6a3f;font-weight:600;">● Aktif</span>
                            @else
                                <span style="color:#c93b2b;font-weight:600;">● Askıda</span>
                            @endif
                        </td>
                        <td style="padding:12px 8px;color:var(--muted);font-size:12px;">
                            {{ $u->created_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td style="padding:12px 8px;text-align:right;">
                            @if(auth()->id() !== $u->id)
                                <div style="display:inline-flex;gap:6px;">
                                    <form method="POST" action="{{ route('admin.users.toggle_role', $u->id) }}">
                                        @csrf
                                        <button type="submit" class="btn secondary" style="padding:4px 8px;font-size:11px;" title="Rolü değiştir">
                                            {{ $u->role === 'admin' ? 'Üye Yap' : 'Admin Yap' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.toggle_status', $u->id) }}">
                                        @csrf
                                        <button type="submit" class="btn secondary" style="padding:4px 8px;font-size:11px;color:{{ $u->status === 'active' ? '#c93b2b' : '#2d6a3f' }};" title="Durumu değiştir">
                                            {{ $u->status === 'active' ? 'Askıya Al' : 'Aktifleştir' }}
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="muted" style="font-size:11px;">(Siz)</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:20px;text-align:center;color:var(--muted);">Kayıtlı üye bulunamadı.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- TABLO 2: Bağlı YouTube Kanalları -->
    <section class="card" style="margin-top:32px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
            <div>
                <div class="eyebrow">İçerik Havuzu</div>
                <h2 style="font:22px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">Son Senkronize Edilen YouTube Kanalları (Son 10)</h2>
            </div>
            <span class="muted" style="font-size:12px;">Toplam {{ $totalChannels }} bağlı kanal</span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
                <thead>
                    <tr style="border-bottom:2px solid var(--line);color:var(--muted);font-weight:600;">
                        <th style="padding:10px 8px;">Kanal</th>
                        <th style="padding:10px 8px;">Kanal ID / Bağlantı</th>
                        <th style="padding:10px 8px;">Kayıtlı Video</th>
                        <th style="padding:10px 8px;">Son Tarama</th>
                        <th style="padding:10px 8px;text-align:right;">YouTube</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentChannels as $c)
                    <tr style="border-bottom:1px solid var(--line);">
                        <td style="padding:12px 8px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                @if($c->thumbnail_url)
                                    <img src="{{ $c->thumbnail_url }}" alt="{{ $c->title }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                @else
                                    <div style="width:36px;height:36px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;">📺</div>
                                @endif
                                <div>
                                    <strong style="color:var(--wine);display:block;">{{ $c->title }}</strong>
                                    @if($c->custom_url)
                                        <span class="muted" style="font-size:11px;">{{ $c->custom_url }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px 8px;font-family:monospace;font-size:12px;color:var(--muted);">{{ $c->youtube_channel_id }}</td>
                        <td style="padding:12px 8px;font-weight:600;">{{ $c->videos_count }} video</td>
                        <td style="padding:12px 8px;color:var(--muted);font-size:12px;">
                            {{ $c->last_synced_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td style="padding:12px 8px;text-align:right;">
                            <a href="https://www.youtube.com/channel/{{ $c->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" class="btn secondary" style="padding:4px 10px;font-size:11px;">
                                Aç ↗
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:20px;text-align:center;color:var(--muted);">Kayıtlı YouTube kanalı bulunamadı.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- TABLO 3: Gelen İletişim & Destek Talepleri -->
    <section class="card" style="margin-top:32px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
            <div>
                <div class="eyebrow">Destek Masası</div>
                <h2 style="font:22px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">Gelen İletişim & Destek Mesajları (Son 10)</h2>
            </div>
            <span class="muted" style="font-size:12px;">Toplam {{ $totalMessages }} başvuru</span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
                <thead>
                    <tr style="border-bottom:2px solid var(--line);color:var(--muted);font-weight:600;">
                        <th style="padding:10px 8px;">Gönderen</th>
                        <th style="padding:10px 8px;">E-Posta</th>
                        <th style="padding:10px 8px;">Konu</th>
                        <th style="padding:10px 8px;">Mesaj</th>
                        <th style="padding:10px 8px;">Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMessages as $m)
                    <tr style="border-bottom:1px solid var(--line);">
                        <td style="padding:12px 8px;font-weight:600;color:var(--wine);">{{ $m['name'] }}</td>
                        <td style="padding:12px 8px;">
                            <a href="mailto:{{ $m['email'] }}" style="color:var(--rust);text-decoration:underline;">{{ $m['email'] }}</a>
                        </td>
                        <td style="padding:12px 8px;">
                            <span style="background:var(--sand);padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600;text-transform:uppercase;">
                                {{ $m['subject'] }}
                            </span>
                        </td>
                        <td style="padding:12px 8px;max-width:340px;line-height:1.4;color:var(--ink);" title="{{ $m['message'] }}">
                            {{ mb_strimwidth($m['message'], 0, 90, '...') }}
                        </td>
                        <td style="padding:12px 8px;color:var(--muted);font-size:12px;white-space:nowrap;">
                            {{ $m['created_at'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:20px;text-align:center;color:var(--muted);">Henüz iletilen destek talebi bulunmuyor.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
