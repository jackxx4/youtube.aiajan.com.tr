@extends('layouts.admin')
@section('title', 'Platform Yönetimi · Genel Bakış')

@section('content')
<div>
    <!-- Başlık Bölümü -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                Yönetici Kontrol Merkezi
            </div>
            <h1 style="font:32px 'DM Serif Display',serif;color:#fff;margin:0 0 6px;">Platform Yönetimi</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:680px;">
                Sistem telemetrisini, YouTube kanallarını, vitrin durumunu ve gelen destek taleplerini canlı izleyin.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.users') }}" class="btn-admin btn-admin-primary">
                <span>👥 Üye Yönetimi ({{ $totalUsers }}) →</span>
            </a>
            <form method="POST" action="{{ route('admin.clear_cache') }}" onsubmit="return confirm('Sistem önbelleklerini temizlemek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="btn-admin btn-admin-secondary">
                    <span>🧹 Önbellek Temizle</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Sistem ve Canlı Altyapı Barı -->
    <div class="ui-card" style="padding:14px 20px;border-left:4px solid var(--accent-rust);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;background:var(--bg-surface-elevated);margin-bottom:24px;">
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;font-family:'JetBrains Mono',monospace;font-size:12px;">
            <span><strong style="color:#fff;">Sürüm:</strong> v{{ $systemInfo['version'] }}</span>
            <span style="color:var(--text-dim);">•</span>
            <span><strong style="color:#fff;">PHP:</strong> {{ $systemInfo['php_version'] }}</span>
            <span style="color:var(--text-dim);">•</span>
            <span><strong style="color:#fff;">Laravel:</strong> v{{ $systemInfo['laravel_ver'] }}</span>
            <span style="color:var(--text-dim);">•</span>
            <span><strong style="color:#fff;">Veritabanı:</strong> {{ strtoupper($systemInfo['db_driver']) }}</span>
            <span style="color:var(--text-dim);">•</span>
            <span><strong style="color:#fff;">Ortam:</strong> <span style="background:var(--accent-rust-soft);color:#ff9b73;padding:2px 6px;border-radius:4px;font-weight:700;">{{ strtoupper($systemInfo['environment']) }}</span></span>
        </div>
        <span style="color:var(--status-ok);font-size:12px;font-weight:600;">🛡️ MIT Sıfır-Güven Aktif</span>
    </div>

    <!-- Metrik Kartları -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Toplam Üye</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-meta">Bugün kaydolan: +{{ $usersToday }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Bağlı YouTube Kanalı</div>
            <div class="stat-value" style="color:var(--accent-rust);">{{ $totalChannels }}</div>
            <div class="stat-meta">Kayıtlı video: {{ $totalVideos }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Vitrindeki Videolar</div>
            <div class="stat-value">{{ $totalShowcaseVideos }}</div>
            <div class="stat-meta">Keşifte yayında olan</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Platform Takipleri</div>
            <div class="stat-value">{{ $totalFollows }}</div>
            <div class="stat-meta">Bugün yapılan: +{{ $followsToday }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Aktif Destekçi</div>
            <div class="stat-value">{{ $activeSupporters }}</div>
            <div class="stat-meta">Özel havuz üyeleri</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">İletişim Talepleri</div>
            <div class="stat-value">{{ $totalMessages }}</div>
            <div class="stat-meta">Gelen mesaj sayısı</div>
        </div>
    </div>

    <!-- Hızlı Üye Yönetimi Çağrısı Kartı -->
    <div class="ui-card" style="background:linear-gradient(135deg, var(--bg-surface-elevated) 0%, #201712 100%);border:1px solid var(--line-border);padding:24px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="font-size:11px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:700;text-transform:uppercase;letter-spacing:1px;">KULLANICI VERİTABANI</div>
            <h2 style="font:22px 'DM Serif Display',serif;margin:4px 0 6px;color:#fff;">Kayıtlı Üyeleri ve Hesapları Yönetin ({{ $totalUsers }} Üye)</h2>
            <p style="color:var(--text-muted);margin:0;max-width:640px;font-size:13px;">
                Tüm kayıtlı üyeleri arayın, rollerini (Admin/Üye) belirleyin, durumlarını askıya alın veya üyeleri tüm verileriyle birlikte kalıcı olarak silin.
            </p>
        </div>
        <a href="{{ route('admin.users') }}" class="btn-admin btn-admin-primary" style="padding:12px 22px;font-size:14px;">
            <span>👥 Üye Yönetimini Aç →</span>
        </a>
    </div>

    <!-- TABLO: Son Eklenen YouTube Kanalları -->
    <section class="ui-card" id="kanallar">
        <div class="ui-card-header">
            <div>
                <div class="stat-label">KANAL VERİTABANI</div>
                <h2 class="ui-card-title">Bağlı YouTube Kanalları</h2>
                <div class="ui-card-subtitle">Sisteme bağlanmış son 10 YouTube kanalı listelenmektedir</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $totalChannels }} kanal</span>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kanal Adı</th>
                        <th>Kanal ID / URL</th>
                        <th>Kayıtlı Video</th>
                        <th>Son Senkronizasyon</th>
                        <th style="text-align:right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentChannels as $ch)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($ch->thumbnail_url)
                                    <img src="{{ $ch->thumbnail_url }}" alt="{{ $ch->title }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--line-border);">
                                @else
                                    <div style="width:36px;height:36px;border-radius:50%;background:var(--bg-surface-elevated);border:1px solid var(--line-border);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--accent-rust);">
                                        {{ mb_substr($ch->title ?: 'Y', 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <strong style="color:#fff;font-size:14px;">{{ $ch->title }}</strong>
                                    @if($ch->subscriber_count)
                                        <div style="font-size:11px;color:var(--text-muted);">{{ number_format($ch->subscriber_count) }} abone</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-muted);font-family:'JetBrains Mono',monospace;font-size:12px;">
                            {{ $ch->custom_url ? $ch->custom_url : $ch->youtube_channel_id }}
                        </td>
                        <td>
                            <span style="background:var(--bg-surface-elevated);border:1px solid var(--line-subtle);padding:3px 9px;border-radius:10px;font-weight:600;font-size:11px;color:#fff;">
                                {{ $ch->videos_count }} video
                            </span>
                        </td>
                        <td style="color:var(--text-muted);font-size:12px;">
                            {{ $ch->last_synced_at ? \Carbon\Carbon::parse($ch->last_synced_at)->diffForHumans() : '—' }}
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <div style="display:inline-flex;gap:8px;align-items:center;">
                                <a href="https://www.youtube.com/{{ $ch->custom_url ? ltrim($ch->custom_url, '/') : 'channel/' . $ch->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" class="btn-admin btn-admin-secondary" style="padding:6px 12px;font-size:11px;">
                                    YouTube ↗
                                </a>
                                <form method="POST" action="{{ route('admin.channels.destroy', $ch->id) }}" style="display:inline;" onsubmit="return confirm('“{{ addslashes($ch->title ?: $ch->youtube_channel_id) }}” kanalını ve bu kanala ait tüm videoları platformdan kalıcı olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz!');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-admin btn-admin-danger" style="padding:6px 12px;font-size:11px;" title="Kanalı Kalıcı Olarak Sil">
                                        🗑️ Kanalı Sil
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:32px;text-align:center;color:var(--text-muted);">
                            Henüz sisteme bağlı bir YouTube kanalı bulunmuyor.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- TABLO: Son Gelen İletişim & Destek Talepleri -->
    <section class="ui-card" id="talepler">
        <div class="ui-card-header">
            <div>
                <div class="stat-label">GERİ BİLDİRİM & İLETİŞİM</div>
                <h2 class="ui-card-title">Gelen Mesajlar ve Destek Talepleri</h2>
                <div class="ui-card-subtitle">İletişim formundan iletilen son talepler</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $totalMessages }} mesaj</span>
        </div>

        @if($recentMessages->isEmpty())
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:13px;">
                Henüz iletişim formundan iletilmiş bir mesaj bulunmuyor.
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($recentMessages as $msg)
                <div style="background:var(--bg-surface-elevated);border-radius:10px;padding:16px 20px;border:1px solid var(--line-subtle);">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                        <div>
                            <strong style="color:#fff;font-size:14px;">{{ $msg['name'] }}</strong>
                            <span style="color:var(--text-muted);font-size:12px;">({{ $msg['email'] }})</span>
                            <span style="background:rgba(200, 90, 50, 0.15);border:1px solid rgba(200, 90, 50, 0.3);color:#ff9b73;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600;margin-left:8px;">
                                {{ $msg['subject'] }}
                            </span>
                        </div>
                        <div style="font-size:11px;color:var(--text-dim);font-family:'JetBrains Mono',monospace;">
                            {{ \Carbon\Carbon::parse($msg['created_at'])->format('d.m.Y H:i') }} (IP: {{ $msg['ip_hash'] }})
                        </div>
                    </div>
                    <p style="margin:0;font-size:13px;line-height:1.55;color:var(--text-main);white-space:pre-wrap;">{{ $msg['message'] }}</p>
                </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
