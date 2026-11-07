@extends('layouts.app')
@section('title', 'Yönetim Merkezi')
@section('content')
<div style="max-width:1160px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px;">
        <div>
            <div class="eyebrow" style="color:var(--rust);">Yönetici Kontrol Merkezi</div>
            <h1 class="h1" style="margin:4px 0 6px;">Platform Yönetimi</h1>
            <p class="lead" style="margin-bottom:0;">Sistem telemetrisini, YouTube kanallarını, vitrin durumunu ve gelen destek taleplerini canlı izleyin.</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <form method="POST" action="{{ route('admin.clear_cache') }}" onsubmit="return confirm('Sistem önbelleklerini temizlemek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="btn secondary" style="padding:10px 16px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                    <span>🧹 Önbelleği Temizle</span>
                </button>
            </form>
            <a href="{{ route('dashboard') }}" class="btn secondary" style="padding:10px 16px;font-size:13px;">
                <span>Kullanıcı Paneline Geç</span>
            </a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn" style="padding:10px 16px;font-size:13px;background:var(--wine);">
                    <span>🚪 Yönetici Çıkışı</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Yönetim Sekmeleri (Tabs) -->
    <div style="display:flex;gap:8px;border-bottom:2px solid var(--line);margin:20px 0 24px;">
        <a href="{{ route('admin.dashboard') }}" style="padding:10px 18px;font-weight:700;font-size:14px;color:var(--wine);border-bottom:3px solid var(--wine);margin-bottom:-2px;text-decoration:none;">
            📊 Genel Bakış
        </a>
        <a href="{{ route('admin.users') }}" style="padding:10px 18px;font-weight:600;font-size:14px;color:var(--muted);border-bottom:3px solid transparent;margin-bottom:-2px;text-decoration:none;">
            👥 Üye Yönetimi ({{ $totalUsers }})
        </a>
    </div>

    <!-- Sistem ve Canlı Altyapı Barı -->
    <div class="card" style="padding:14px 20px;background:#fbf7f2;border-left:4px solid var(--wine);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;">
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

    <!-- Hızlı Üye Yönetimi Çağrısı -->
    <div class="card" style="margin-top:24px;background:linear-gradient(135deg, #fffdfa 0%, #f7efe5 100%);border:1px solid #e7d8ca;padding:24px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <div class="eyebrow">Üye Yönetimi Modülü</div>
            <h2 style="font:24px 'DM Serif Display',serif;margin:4px 0 6px;color:var(--wine);">Kayıtlı Kullanıcıları Yönetin ({{ $totalUsers }} Üye)</h2>
            <p class="muted" style="margin:0;max-width:600px;font-size:13px;">
                Tüm kayıtlı üyeleri arayın, rollerini (Admin/Üye) belirleyin, durumlarını askıya alın veya üyeleri kalıcı olarak silin.
            </p>
        </div>
        <a href="{{ route('admin.users') }}" class="btn" style="padding:12px 24px;font-size:14px;white-space:nowrap;display:inline-flex;align-items:center;gap:8px;">
            <span>👥 Üye Yönetimini Aç →</span>
        </a>
    </div>

    <!-- TABLO: Son Eklenen YouTube Kanalları -->
    <section class="card" style="margin-top:28px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
            <div>
                <div class="eyebrow">Kanal Veritabanı</div>
                <h2 style="font:22px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">Son Bağlanan YouTube Kanalları</h2>
            </div>
            <span class="muted" style="font-size:12px;">Toplam {{ $totalChannels }} kanaldan son 10'u listeleniyor</span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
                <thead>
                    <tr style="border-bottom:2px solid var(--line);color:var(--muted);font-weight:600;">
                        <th style="padding:10px 8px;">Kanal Adı</th>
                        <th style="padding:10px 8px;">Kanal ID / URL</th>
                        <th style="padding:10px 8px;">Kayıtlı Video</th>
                        <th style="padding:10px 8px;">Son Senkronizasyon</th>
                        <th style="padding:10px 8px;text-align:right;">Bağlantı</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentChannels as $ch)
                    <tr style="border-bottom:1px solid var(--line);">
                        <td style="padding:12px 8px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                @if($ch->thumbnail_url)
                                    <img src="{{ $ch->thumbnail_url }}" alt="{{ $ch->title }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;">
                                @else
                                    <div style="width:34px;height:34px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);">
                                        {{ mb_substr($ch->title, 0, 1) }}
                                    </div>
                                @endif
                                <strong style="color:var(--wine);">{{ $ch->title }}</strong>
                            </div>
                        </td>
                        <td style="padding:12px 8px;color:var(--muted);font-family:monospace;font-size:12px;">
                            {{ $ch->custom_url ? $ch->custom_url : $ch->youtube_channel_id }}
                        </td>
                        <td style="padding:12px 8px;">
                            <span style="background:var(--sand);padding:3px 8px;border-radius:10px;font-weight:600;font-size:11px;">
                                {{ $ch->videos_count }} video
                            </span>
                        </td>
                        <td style="padding:12px 8px;color:var(--muted);font-size:12px;">
                            {{ $ch->last_synced_at ? \Carbon\Carbon::parse($ch->last_synced_at)->diffForHumans() : '—' }}
                        </td>
                        <td style="padding:12px 8px;text-align:right;white-space:nowrap;">
                            <div style="display:inline-flex;gap:6px;align-items:center;">
                                <a href="https://www.youtube.com/{{ $ch->custom_url ? ltrim($ch->custom_url, '/') : 'channel/' . $ch->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" class="btn secondary" style="padding:5px 10px;font-size:11px;">
                                    YouTube ↗
                                </a>
                                <form method="POST" action="{{ route('admin.channels.destroy', $ch->id) }}" style="display:inline;" onsubmit="return confirm('“{{ addslashes($ch->title ?: $ch->youtube_channel_id) }}” kanalını ve bu kanala ait tüm videoları platformdan kalıcı olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz!');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn secondary" style="padding:5px 10px;font-size:11px;color:#fff;background:#c93b2b;border-color:#c93b2b;cursor:pointer;" title="Kanalı Kalıcı Olarak Sil">
                                        🗑️ Kanalı Sil
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:24px;text-align:center;color:var(--muted);">Henüz bağlı kanal bulunmuyor.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- TABLO: Son Gelen İletişim & Destek Talepleri -->
    <section class="card" style="margin-top:28px;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
            <div>
                <div class="eyebrow">Geri Bildirim & İletişim</div>
                <h2 style="font:22px 'DM Serif Display',serif;margin:2px 0 0;color:var(--wine);">Gelen Mesajlar ve Destek Talepleri</h2>
            </div>
            <span class="muted" style="font-size:12px;">Toplam {{ $totalMessages }} mesajdan son 10'u</span>
        </div>

        @if($recentMessages->isEmpty())
            <p class="muted" style="margin:0;padding:12px 0;">Henüz iletişim formundan gönderilmiş bir mesaj bulunmuyor.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($recentMessages as $msg)
                <div style="background:var(--sand);border-radius:10px;padding:14px 18px;border:1px solid var(--line);">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                        <div>
                            <strong style="color:var(--wine);font-size:14px;">{{ $msg['name'] }}</strong>
                            <span class="muted" style="font-size:12px;">({{ $msg['email'] }})</span>
                            <span style="background:var(--cream);border:1px solid var(--line);padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;margin-left:6px;color:var(--rust);">
                                {{ $msg['subject'] }}
                            </span>
                        </div>
                        <div class="muted" style="font-size:11px;">
                            {{ \Carbon\Carbon::parse($msg['created_at'])->format('d.m.Y H:i') }} (IP: {{ $msg['ip_hash'] }})
                        </div>
                    </div>
                    <p style="margin:0;font-size:13px;line-height:1.5;color:var(--ink);white-space:pre-wrap;">{{ $msg['message'] }}</p>
                </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
