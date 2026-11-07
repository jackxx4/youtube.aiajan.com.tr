@extends('layouts.admin')
@section('title', 'Kanal Yönetimi · Yönetim Merkezi')

@section('content')
<div>
    <!-- Başlık Bölümü -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                YÖNETİCİ KONTROL MERKEZİ · KANAL VERİTABANI
            </div>
            <h1 style="font:32px 'DM Serif Display',serif;color:#fff;margin:0 0 6px;">Kanal Yönetimi</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:680px;">
                Platform veritabanındaki tüm YouTube kanallarını, ilişkili videolarını ve bağlı olduğu kullanıcı hesaplarını yönetin veya silin.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            @if($orphanCount > 0)
                <form method="POST" action="{{ route('admin.channels.clean_orphans') }}" onsubmit="return confirm('Hiçbir kullanıcıya bağlı olmayan {{ $orphanCount }} adet sahipsiz kanalı ve videolarını tamamen silmek istediğinize emin misiniz?');">
                    @csrf
                    <button type="submit" class="btn-admin btn-admin-danger" style="padding:10px 16px;">
                        <span>🧹 Sahipsiz Kanalları Temizle ({{ $orphanCount }})</span>
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.dashboard') }}" class="btn-admin btn-admin-secondary">
                <span>← Genel Bakış</span>
            </a>
        </div>
    </div>

    <!-- Sahipsiz Kanal Uyarısı (Eğer varsa) -->
    @if($orphanCount > 0)
        <div class="ui-card" style="padding:16px 20px;background:rgba(251, 191, 36, 0.08);border:1px solid rgba(251, 191, 36, 0.3);margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:20px;">⚠️</span>
                <div>
                    <strong style="color:#fbbf24;font-size:14px;">{{ $orphanCount }} Adet Sahipsiz Kanal Tespit Edildi</strong>
                    <div style="color:var(--text-muted);font-size:12px;margin-top:2px;">
                        Bu kanalların bağlı olduğu kullanıcı hesabı silinmiş veya eşleşmesi kaldırılmış. Dilerseniz tek tıkla temizleyebilirsiniz.
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.channels.clean_orphans') }}" onsubmit="return confirm('Sahipsiz kanalları silmek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="btn-admin btn-admin-danger" style="padding:7px 14px;font-size:12px;">
                    Tüm Sahipsiz Kanalları Temizle
                </button>
            </form>
        </div>
    @endif

    <!-- Arama ve Filtreleme Kartı -->
    <div class="ui-card" style="padding:18px 22px;margin-bottom:24px;">
        <form method="GET" action="{{ route('admin.channels') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="flex:1;min-width:260px;">
                <input type="text" name="search" value="{{ $search }}" class="admin-input" placeholder="Kanal adı, YouTube Kanal ID (UC...) veya URL ile ara..." style="margin:0;">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding:10px 18px;">
                    🔍 Filtrele
                </button>
                @if($search !== '')
                    <a href="{{ route('admin.channels') }}" class="btn-admin btn-admin-secondary" style="padding:10px 16px;">
                        Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Kanallar Tablosu Kartı -->
    <div class="ui-card">
        <div class="ui-card-header">
            <div>
                <h2 class="ui-card-title">
                    Kayıtlı YouTube Kanalları
                    @if($search !== '')
                        <span style="font-size:14px;color:var(--text-muted);font-weight:400;">("{{ $search }}" için sonuçlar)</span>
                    @endif
                </h2>
                <div class="ui-card-subtitle">Veritabanında kayıtlı toplam {{ $channels->total() }} kanal bulunmaktadır</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $totalChannels }} kanal</span>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kanal</th>
                        <th>Kanal ID / URL</th>
                        <th>Bağlı Üye</th>
                        <th>Video Sayısı</th>
                        <th>Son Senkronizasyon</th>
                        <th style="text-align:right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($channels as $ch)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($ch->thumbnail_url)
                                    <img src="{{ $ch->thumbnail_url }}" alt="{{ $ch->title }}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:1px solid var(--line-border);">
                                @else
                                    <div style="width:38px;height:38px;border-radius:50%;background:var(--bg-surface-elevated);border:1px solid var(--line-border);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--accent-rust);">
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
                            {{ $ch->custom_url ?: $ch->youtube_channel_id }}
                        </td>
                        <td>
                            @php
                                $linkedUsers = $ch->links->pluck('user')->filter();
                            @endphp
                            @if($linkedUsers->isNotEmpty())
                                @foreach($linkedUsers as $u)
                                    <a href="{{ route('profile.show', $u->username) }}" target="_blank" style="color:var(--status-ok);font-size:12px;font-weight:600;text-decoration:underline;display:inline-block;margin-right:6px;">
                                        👤 {{ $u->name }}
                                    </a>
                                @endforeach
                            @else
                                <span style="background:rgba(251, 191, 36, 0.15);color:#fbbf24;border:1px solid rgba(251, 191, 36, 0.3);padding:2px 7px;border-radius:6px;font-size:11px;font-weight:600;">
                                    ⚠️ Sahipsiz Kanal
                                </span>
                            @endif
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
                        <td colspan="6" style="padding:32px;text-align:center;color:var(--text-muted);">
                            Aradığınız kriterlere uygun YouTube kanalı bulunamadı.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Sayfalama -->
        @if($channels->hasPages())
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-subtle);display:flex;justify-content:center;">
                {{ $channels->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

