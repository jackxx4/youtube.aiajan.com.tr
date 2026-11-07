@extends('layouts.admin')
@section('title', 'Platform Yönetimi · Genel Bakış')

@section('content')
<div>
    <!-- Başlık ve Hızlı Eylemler -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                Yönetici Kontrol Merkezi
            </div>
            <h1 style="font:30px 'DM Serif Display',serif;color:#fff;margin:0 0 4px;">Platform Yönetimi</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">
                Platformdaki üyeleri, kanalları, kullanıcıların vitrine koyduğu videoları ve destek taleplerini izleyin.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.showcase_videos') }}" class="btn-admin btn-admin-primary">
                <span>✨ Vitrin Videoları ({{ $totalShowcaseVideos }})</span>
            </a>
            <a href="{{ route('admin.channels') }}" class="btn-admin btn-admin-secondary">
                <span>📺 Kanal Yönetimi ({{ $totalChannels }})</span>
            </a>
            <a href="{{ route('admin.admins') }}" class="btn-admin btn-admin-secondary">
                <span>🛡️ Yöneticiler ({{ $totalAdmins }})</span>
            </a>
        </div>
    </div>

    <!-- Metrik Kartları -->
    <div class="stats-grid">
        <a href="{{ route('admin.users') }}" class="stat-card" style="display:block;">
            <div class="stat-label">Toplam Üye</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-meta">Bugün kaydolan: +{{ $usersToday }}</div>
        </a>

        <a href="{{ route('admin.channels') }}" class="stat-card" style="display:block;">
            <div class="stat-label">Bağlı YouTube Kanalı</div>
            <div class="stat-value" style="color:var(--accent-rust);">{{ $totalChannels }}</div>
            <div class="stat-meta">Kayıtlı video: {{ $totalVideos }}</div>
        </a>

        <a href="{{ route('admin.showcase_videos') }}" class="stat-card" style="display:block;">
            <div class="stat-label">Vitrindeki Videolar</div>
            <div class="stat-value" style="color:#ffbba1;">{{ $totalShowcaseVideos }}</div>
            <div class="stat-meta">Keşifte yayında olan</div>
        </a>

        <a href="{{ route('admin.admins') }}" class="stat-card" style="display:block;">
            <div class="stat-label">Yöneticiler</div>
            <div class="stat-value" style="color:var(--status-ok);">{{ $totalAdmins }}</div>
            <div class="stat-meta">Müstakil Admin Hesabı</div>
        </a>

        <div class="stat-card">
            <div class="stat-label">Platform Takipleri</div>
            <div class="stat-value">{{ $totalFollows }}</div>
            <div class="stat-meta">Bugün yapılan: +{{ $followsToday }}</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">İletişim Talepleri</div>
            <div class="stat-value">{{ $totalMessages }}</div>
            <div class="stat-meta">Gelen destek mesajı</div>
        </div>
    </div>

    <!-- TABLO 1: Vitrindeki Videolar (Kullanıcıların Ekledikleri) -->
    <section class="ui-card" style="margin-bottom:28px;">
        <div class="ui-card-header">
            <div>
                <div class="stat-label" style="color:var(--accent-rust);">KEŞİF HAVUZU</div>
                <h2 class="ui-card-title">Vitrindeki Videolar (Kullanıcıların Ekledikleri)</h2>
                <div class="ui-card-subtitle">Kullanıcıların kendi kanal veya hesaplarından havuza seçtiği son videolar</div>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $totalShowcaseVideos }} video</span>
                @if($totalShowcaseVideos > 0)
                    <a href="{{ route('admin.showcase_videos') }}" class="btn-admin btn-admin-secondary" style="padding:5px 12px;font-size:12px;">
                        Tümünü Gör →
                    </a>
                @endif
            </div>
        </div>

        @if($recentShowcaseVideos->isEmpty())
            <div style="padding:36px;text-align:center;color:var(--text-muted);font-size:13px;background:var(--bg-surface-elevated);border-radius:10px;border:1px dashed var(--line-subtle);">
                <div style="font-size:24px;margin-bottom:6px;">✨</div>
                <strong style="color:#fff;">Henüz Vitrine Eklenmiş Video Bulunmuyor</strong>
                <p style="margin:4px 0 0;font-size:12px;">Kullanıcılar kanallarından video seçip onayladıkça burada ve keşif havuzunda listelenecektir.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Video</th>
                            <th>Ekleyen Kullanıcı</th>
                            <th>Ait Olduğu Kanal</th>
                            <th>Seçim Tarihi</th>
                            <th style="text-align:right;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentShowcaseVideos as $sel)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    @if($sel->video?->thumbnail_url)
                                        <img src="{{ $sel->video->thumbnail_url }}" alt="{{ $sel->video->title }}" style="width:70px;aspect-ratio:16/9;border-radius:6px;object-fit:cover;border:1px solid var(--line-border);">
                                    @else
                                        <div style="width:70px;aspect-ratio:16/9;background:var(--bg-surface-elevated);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px;">
                                            🎬
                                        </div>
                                    @endif
                                    <div style="max-width:320px;">
                                        <a href="https://www.youtube.com/watch?v={{ $sel->video?->youtube_video_id }}" target="_blank" rel="noopener noreferrer" style="color:#fff;font-weight:600;font-size:13px;line-height:1.35;text-decoration:none;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            {{ $sel->video?->title ?: 'Video #' . $sel->video_id }}
                                        </a>
                                        <div style="font-size:11px;color:var(--text-dim);font-family:'JetBrains Mono',monospace;margin-top:2px;">
                                            ID: {{ $sel->video?->youtube_video_id }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($sel->user)
                                    <div>
                                        <a href="{{ route('profile.show', $sel->user->username) }}" target="_blank" style="color:var(--text-main);font-weight:600;text-decoration:underline;">
                                            {{ $sel->user->name }}
                                        </a>
                                        <div style="font-size:11px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">{{ $sel->user->email }}</div>
                                    </div>
                                @else
                                    <span style="color:var(--text-dim);">—</span>
                                @endif
                            </td>
                            <td>
                                @if($sel->video?->channel)
                                    <span style="color:var(--accent-rust);font-weight:600;">
                                        📺 {{ Str::limit($sel->video->channel->title, 22) }}
                                    </span>
                                @else
                                    <span style="color:var(--text-dim);">—</span>
                                @endif
                            </td>
                            <td style="color:var(--text-muted);font-size:12px;font-family:'JetBrains Mono',monospace;white-space:nowrap;">
                                {{ $sel->created_at ? \Carbon\Carbon::parse($sel->created_at)->diffForHumans() : '—' }}
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <div style="display:inline-flex;gap:6px;align-items:center;">
                                    <a href="https://www.youtube.com/watch?v={{ $sel->video?->youtube_video_id }}" target="_blank" rel="noopener noreferrer" class="btn-admin btn-admin-secondary" style="padding:5px 9px;font-size:11px;">
                                        İzle ↗
                                    </a>
                                    <form method="POST" action="{{ route('admin.showcase_videos.destroy', $sel->id) }}" style="display:inline;" onsubmit="return confirm('Bu videoyu vitrinden kaldırmak istediğinize emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-admin btn-admin-danger" style="padding:5px 9px;font-size:11px;" title="Vitrinden Kaldır">
                                            🗑️ Vitrinden Kaldır
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <!-- TABLO 2: Son Gelen İletişim & Destek Talepleri -->
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
            <div style="padding:28px;text-align:center;color:var(--text-muted);font-size:13px;background:var(--bg-surface-elevated);border-radius:10px;">
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
