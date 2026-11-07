@extends('layouts.admin')
@section('title', 'Platform Yönetimi · Genel Bakış')

@section('content')
<div>
    <!-- Sayfa Başlığı -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                Yönetici Kontrol Merkezi
            </div>
            <h1 style="font:28px 'DM Serif Display',serif;color:#fff;margin:0 0 4px;">Platform Genel Bakış</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">
                Platformun genel metriklerini, en son takiplerini ve vitrin hareketlerini izleyin.
            </p>
        </div>
        <div>
            <form method="POST" action="{{ route('admin.clear_cache') }}" onsubmit="return confirm('Sistem önbelleklerini temizlemek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="btn-admin btn-admin-secondary">
                    <span>🧹 Önbelleği Temizle</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 6 Temel Metrik Kartı (Her biri kendi müstakil sayfasına gider) -->
    <div class="stats-grid">
        <a href="{{ route('admin.users') }}" class="stat-card" style="display:block;">
            <div class="stat-label">👥 TOPLAM ÜYE</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-meta">Bugün kaydolan: +{{ $usersToday }}</div>
        </a>

        <a href="{{ route('admin.channels') }}" class="stat-card" style="display:block;">
            <div class="stat-label">📺 BAĞLI KANAL</div>
            <div class="stat-value" style="color:var(--accent-rust);">{{ $totalChannels }}</div>
            <div class="stat-meta">Kayıtlı video: {{ $totalVideos }}</div>
        </a>

        <a href="{{ route('admin.showcase_videos') }}" class="stat-card" style="display:block;">
            <div class="stat-label">✨ VİTRİNDEKİ VİDEOLAR</div>
            <div class="stat-value" style="color:#ffbba1;">{{ $totalShowcaseVideos }}</div>
            <div class="stat-meta">Keşifte yayında olan</div>
        </a>

        <a href="{{ route('admin.follows') }}" class="stat-card" style="display:block;">
            <div class="stat-label">🤝 PLATFORM TAKİPLERİ</div>
            <div class="stat-value" style="color:var(--status-ok);">{{ $totalFollows }}</div>
            <div class="stat-meta">Bugün yapılan: +{{ $followsToday }}</div>
        </a>

        <a href="{{ route('admin.messages') }}" class="stat-card" style="display:block;">
            <div class="stat-label">💬 İLETİŞİM TALEPLERİ</div>
            <div class="stat-value">{{ $totalMessages }}</div>
            <div class="stat-meta">Gelen destek talebi</div>
        </a>

        <a href="{{ route('admin.admins') }}" class="stat-card" style="display:block;">
            <div class="stat-label">🛡️ YÖNETİCİLER</div>
            <div class="stat-value" style="color:var(--status-success);">{{ $totalAdmins }}</div>
            <div class="stat-meta">Müstakil Admin Hesabı</div>
        </a>
    </div>

    <!-- İki Sütunlu Kompakt Özet: Son Takipler & Son Vitrin Videoları -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(420px, 1fr));gap:20px;margin-top:8px;">
        <!-- Son Takipler Özeti -->
        <div class="ui-card" style="margin-bottom:0;">
            <div class="ui-card-header" style="margin-bottom:14px;">
                <div>
                    <div class="stat-label" style="color:var(--status-ok);">HAREKETLER</div>
                    <h2 class="ui-card-title" style="font-size:18px;">Son Platform Takipleri</h2>
                </div>
                <a href="{{ route('admin.follows') }}" class="btn-admin btn-admin-secondary" style="padding:4px 10px;font-size:11px;">
                    Tümünü Gör ({{ $totalFollows }}) →
                </a>
            </div>

            @if($recentFollows->isEmpty())
                <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:12px;background:var(--bg-surface-elevated);border-radius:8px;">
                    Henüz platformda yapılmış bir takip / abone işlemi bulunmuyor.
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($recentFollows as $f)
                    <div style="background:var(--bg-surface-elevated);padding:10px 14px;border-radius:8px;border:1px solid var(--line-subtle);display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:12px;">
                        <div>
                            <strong style="color:#fff;">{{ $f->follower?->name ?: 'Üye' }}</strong>
                            <span style="color:var(--text-muted);">takip etti →</span>
                            <span style="color:var(--accent-rust);font-weight:600;">{{ $f->channel?->title ?: ($f->followed?->name ?: 'Kanal') }}</span>
                            <span style="background:rgba(52, 211, 153, 0.15);color:var(--status-ok);padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;margin-left:4px;">
                                {{ $f->pool_type === 'supporter' ? '⭐ Destekçi' : '👥 Normal' }}
                            </span>
                        </div>
                        <div style="color:var(--text-dim);font-family:'JetBrains Mono',monospace;font-size:11px;white-space:nowrap;">
                            {{ $f->created_at ? $f->created_at->diffForHumans() : '—' }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Son Vitrin Videoları Özeti -->
        <div class="ui-card" style="margin-bottom:0;">
            <div class="ui-card-header" style="margin-bottom:14px;">
                <div>
                    <div class="stat-label" style="color:var(--accent-rust);">KEŞİF HAVUZU</div>
                    <h2 class="ui-card-title" style="font-size:18px;">Son Eklenen Vitrin Videoları</h2>
                </div>
                <a href="{{ route('admin.showcase_videos') }}" class="btn-admin btn-admin-secondary" style="padding:4px 10px;font-size:11px;">
                    Tümünü Gör ({{ $totalShowcaseVideos }}) →
                </a>
            </div>

            @if($recentShowcaseVideos->isEmpty())
                <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:12px;background:var(--bg-surface-elevated);border-radius:8px;">
                    Henüz vitrine eklenmiş bir video bulunmuyor.
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($recentShowcaseVideos as $sel)
                    <div style="background:var(--bg-surface-elevated);padding:8px 12px;border-radius:8px;border:1px solid var(--line-subtle);display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
                            @if($sel->video?->thumbnail_url)
                                <img src="{{ $sel->video->thumbnail_url }}" alt="" style="width:48px;aspect-ratio:16/9;border-radius:4px;object-fit:cover;flex-shrink:0;">
                            @endif
                            <div style="overflow:hidden;">
                                <div style="color:#fff;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $sel->video?->title ?: 'Video' }}
                                </div>
                                <div style="color:var(--text-muted);font-size:11px;">
                                    Ekleyen: {{ $sel->user?->name ?: 'Üye' }} • {{ $sel->video?->channel?->title ?: 'Kanal' }}
                                </div>
                            </div>
                        </div>
                        <a href="https://www.youtube.com/watch?v={{ $sel->video?->youtube_video_id }}" target="_blank" rel="noopener noreferrer" class="btn-admin btn-admin-secondary" style="padding:3px 8px;font-size:11px;flex-shrink:0;">
                            İzle ↗
                        </a>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
