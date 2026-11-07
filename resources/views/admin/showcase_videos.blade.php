@extends('layouts.admin')
@section('title', 'Vitrin Videoları · Yönetim Merkezi')

@section('content')
<div>
    <!-- Başlık Bölümü -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                KEŞİF HAVUZU · VİTRİN YÖNETİMİ
            </div>
            <h1 style="font:32px 'DM Serif Display',serif;color:#fff;margin:0 0 6px;">Vitrin Videoları</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:680px;">
                Kullanıcıların kendi kanal veya hesaplarından seçerek topluluk keşif havuzuna koyduğu tüm videolar.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.dashboard') }}" class="btn-admin btn-admin-secondary">
                <span>← Genel Bakış</span>
            </a>
        </div>
    </div>

    <!-- Arama Kartı -->
    <div class="ui-card" style="padding:18px 22px;margin-bottom:24px;">
        <form method="GET" action="{{ route('admin.showcase_videos') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="flex:1;min-width:260px;">
                <input type="text" name="search" value="{{ $search }}" class="admin-input" placeholder="Video başlığı, YouTube ID veya kullanıcı adı/e-posta ile ara..." style="margin:0;">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding:10px 18px;">
                    🔍 Filtrele
                </button>
                @if($search !== '')
                    <a href="{{ route('admin.showcase_videos') }}" class="btn-admin btn-admin-secondary" style="padding:10px 16px;">
                        Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Videolar Tablosu Kartı -->
    <div class="ui-card">
        <div class="ui-card-header">
            <div>
                <h2 class="ui-card-title">
                    Vitrinde Yayında Olan Videolar
                    @if($search !== '')
                        <span style="font-size:14px;color:var(--text-muted);font-weight:400;">("{{ $search }}" için sonuçlar)</span>
                    @endif
                </h2>
                <div class="ui-card-subtitle">Bu videolar topluluk keşif havuzunda diğer üreticilere gösterilmektedir</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $totalShowcaseVideos }} video</span>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Video</th>
                        <th>Ekleyen Kullanıcı</th>
                        <th>Kanal</th>
                        <th>Seçim Tarihi</th>
                        <th style="text-align:right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($selections as $sel)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($sel->video?->thumbnail_url)
                                    <img src="{{ $sel->video->thumbnail_url }}" alt="{{ $sel->video->title }}" style="width:74px;aspect-ratio:16/9;border-radius:6px;object-fit:cover;border:1px solid var(--line-border);">
                                @else
                                    <div style="width:74px;aspect-ratio:16/9;background:var(--bg-surface-elevated);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px;">
                                        🎬
                                    </div>
                                @endif
                                <div style="max-width:340px;">
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
                                    YouTube ↗
                                </a>
                                <form method="POST" action="{{ route('admin.showcase_videos.destroy', $sel->id) }}" style="display:inline;" onsubmit="return confirm('Bu videoyu vitrinden kaldırmak istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-admin btn-admin-danger" style="padding:5px 9px;font-size:11px;" title="Vitrinden Kaldır">
                                        🗑️ Kaldır
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:32px;text-align:center;color:var(--text-muted);">
                            Vitrinde henüz video bulunmuyor.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($selections->hasPages())
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-subtle);display:flex;justify-content:center;">
                {{ $selections->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

