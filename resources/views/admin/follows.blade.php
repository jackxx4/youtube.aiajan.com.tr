@extends('layouts.admin')
@section('title', 'Platform Takipleri · Yönetim Merkezi')

@section('content')
<div>
    <!-- Başlık Bölümü -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--status-ok);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                HAREKETLER & ETKİLEŞİM
            </div>
            <h1 style="font:28px 'DM Serif Display',serif;color:#fff;margin:0 0 4px;">Platform Takipleri</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:680px;">
                Kullanıcıların keşif ve destekçi havuzları üzerinden gerçekleştirdiği tüm YouTube kanalı abone olma / takip kayıtları.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.dashboard') }}" class="btn-admin btn-admin-secondary">
                <span>← Genel Bakış</span>
            </a>
        </div>
    </div>

    <!-- Başlıklara Göre Filtre Sekmeleri -->
    <div style="display:flex;gap:8px;border-bottom:1px solid var(--line-subtle);margin-bottom:22px;flex-wrap:wrap;">
        <a href="{{ route('admin.follows') }}" style="padding:10px 16px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid {{ empty($pool) ? 'var(--accent-rust)' : 'transparent' }};color:{{ empty($pool) ? '#fff' : 'var(--text-muted)' }};">
            Tüm Takipler ({{ $totalFollows }})
        </a>
        <a href="{{ route('admin.follows', ['pool' => 'normal']) }}" style="padding:10px 16px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid {{ $pool === 'normal' ? 'var(--accent-rust)' : 'transparent' }};color:{{ $pool === 'normal' ? '#fff' : 'var(--text-muted)' }};">
            👥 Normal Keşif Takipleri ({{ $normalCount }})
        </a>
        <a href="{{ route('admin.follows', ['pool' => 'supporter']) }}" style="padding:10px 16px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid {{ $pool === 'supporter' ? 'var(--accent-rust)' : 'transparent' }};color:{{ $pool === 'supporter' ? '#fff' : 'var(--text-muted)' }};">
            ⭐ Destekçi Havuzu Takipleri ({{ $supporterCount }})
        </a>
    </div>

    <!-- Arama Kartı -->
    <div class="ui-card" style="padding:16px 20px;margin-bottom:24px;">
        <form method="GET" action="{{ route('admin.follows') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            @if(!empty($pool))
                <input type="hidden" name="pool" value="{{ $pool }}">
            @endif
            <div style="flex:1;min-width:260px;">
                <input type="text" name="search" value="{{ $search }}" class="admin-input" placeholder="Takip eden üye adı, takip edilen kanal veya ID ile ara..." style="margin:0;">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding:9px 16px;">
                    🔍 Filtrele
                </button>
                @if($search !== '' || !empty($pool))
                    <a href="{{ route('admin.follows') }}" class="btn-admin btn-admin-secondary" style="padding:9px 14px;">
                        Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Takipler Tablosu -->
    <div class="ui-card">
        <div class="ui-card-header">
            <div>
                <h2 class="ui-card-title">
                    Kayıtlı Takipler
                    @if($search !== '')
                        <span style="font-size:13px;color:var(--text-muted);font-weight:400;">("{{ $search }}" için sonuçlar)</span>
                    @endif
                </h2>
                <div class="ui-card-subtitle">Platform üzerinden yapılan YouTube abone olma ve takip hareketleri</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $follows->total() }} kayıt</span>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Takip Eden Üye</th>
                        <th>Takip Edilen Kanal / Üye</th>
                        <th>Takip Havuzu</th>
                        <th>İşlem Tarihi</th>
                        <th style="text-align:right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($follows as $f)
                    <tr>
                        <td>
                            <div>
                                <strong style="color:#fff;font-size:13px;">{{ $f->follower?->name ?: 'Silinmiş Kullanıcı' }}</strong>
                                <div style="font-size:11px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">
                                    {{ $f->follower?->email ?: '—' }}
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                @if($f->channel)
                                    <a href="https://www.youtube.com/{{ $f->channel->custom_url ? ltrim($f->channel->custom_url, '/') : 'channel/' . $f->channel->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" style="color:var(--accent-rust);font-weight:600;text-decoration:underline;">
                                        📺 {{ $f->channel->title }}
                                    </a>
                                    <div style="font-size:11px;color:var(--text-dim);font-family:'JetBrains Mono',monospace;">
                                        {{ $f->channel->youtube_channel_id }}
                                    </div>
                                @elseif($f->followed)
                                    <strong style="color:var(--text-main);">{{ $f->followed->name }}</strong>
                                    <div style="font-size:11px;color:var(--text-dim);font-family:'JetBrains Mono',monospace;">{{ $f->followed->email }}</div>
                                @else
                                    <span style="color:var(--text-dim);">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($f->pool_type === 'supporter')
                                <span style="background:rgba(251, 191, 36, 0.15);color:#fbbf24;border:1px solid rgba(251, 191, 36, 0.3);padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600;">
                                    ⭐ Destekçi Havuzu
                                </span>
                            @else
                                <span style="background:var(--bg-surface-elevated);color:var(--status-ok);border:1px solid var(--line-subtle);padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600;">
                                    👥 Normal Keşif
                                </span>
                            @endif
                        </td>
                        <td style="color:var(--text-muted);font-size:12px;font-family:'JetBrains Mono',monospace;white-space:nowrap;">
                            {{ $f->created_at ? $f->created_at->format('d.m.Y H:i') : '—' }}
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <form method="POST" action="{{ route('admin.follows.destroy', $f->id) }}" style="display:inline;" onsubmit="return confirm('Bu takip kaydını silmek istediğinize emin misiniz?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-admin btn-admin-danger" style="padding:4px 9px;font-size:11px;" title="Takibi Sil">
                                    🗑️ Sil
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:32px;text-align:center;color:var(--text-muted);">
                            Seçilen kriterlere uygun takip kaydı bulunamadı.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($follows->hasPages())
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-subtle);display:flex;justify-content:center;">
                {{ $follows->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

