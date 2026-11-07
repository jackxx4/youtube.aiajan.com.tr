@extends('layouts.admin')
@section('title', 'Kullanıcı ve Hesap Yönetimi')

@section('content')
<div>
    <!-- Başlık Bölümü -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                YÖNETİCİ KONTROL MERKEZİ · KULLANICI YÖNETİMİ
            </div>
            <h1 style="font:32px 'DM Serif Display',serif;color:#fff;margin:0 0 6px;">Kullanıcı Yönetimi</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:680px;">
                Platforma kayıtlı tüm kullanıcıları görüntüleyin, durumlarını (aktif/askıda) yönetin, rollerini belirleyin veya kalıcı olarak silin.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.dashboard') }}" class="btn-admin btn-admin-secondary">
                <span>← Genel Bakış</span>
            </a>
        </div>
    </div>

    <!-- Arama ve Filtreleme Kartı -->
    @if($users->isEmpty() && $totalChannels > 0)
        <div class="ui-card" style="padding:16px 20px;background:rgba(56, 189, 248, 0.08);border:1px solid rgba(56, 189, 248, 0.25);margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:20px;">ℹ️</span>
                <div>
                    <strong style="color:var(--status-success);font-size:14px;">Kullanıcı Listesi Boş, Ancak Veritabanında {{ $totalChannels }} Kanal Kayıtlı</strong>
                    <div style="color:var(--text-muted);font-size:12px;margin-top:2px;">
                        Kullanıcı hesabı silinmiş olan bağımsız kanalları doğrudan Kanal Yönetimi sayfasından yönetebilir veya silebilirsiniz.
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.channels') }}" class="btn-admin btn-admin-primary" style="padding:8px 16px;font-size:12px;">
                📺 Kanal Yönetimine Git ({{ $totalChannels }} Kanal) →
            </a>
        </div>
    @endif

    <div class="ui-card" style="padding:18px 22px;margin-bottom:24px;">
        <form method="GET" action="{{ route('admin.users') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="flex:2;min-width:240px;">
                <input type="text" name="search" value="{{ $search }}" class="admin-input" placeholder="İsim, kullanıcı adı veya e-posta ile ara..." style="margin:0;">
            </div>
            <div style="min-width:140px;">
                <select name="role" class="admin-input" style="margin:0;">
                    <option value="">Tüm Roller</option>
                    <option value="user" {{ $role === 'user' ? 'selected' : '' }}>Üye</option>
                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Yönetici</option>
                </select>
            </div>
            <div style="min-width:140px;">
                <select name="status" class="admin-input" style="margin:0;">
                    <option value="">Tüm Durumlar</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Askıda</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding:10px 18px;">
                    🔍 Filtrele
                </button>
                @if($search !== '' || $role || $status)
                    <a href="{{ route('admin.users') }}" class="btn-admin btn-admin-secondary" style="padding:10px 16px;">
                        Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Kullanıcılar Tablosu Kartı -->
    <div class="ui-card">
        <div class="ui-card-header">
            <div>
                <h2 class="ui-card-title">
                    Kayıtlı Üyeler
                    @if($search !== '')
                        <span style="font-size:14px;color:var(--text-muted);font-weight:400;">("{{ $search }}" için sonuçlar)</span>
                    @endif
                </h2>
                <div class="ui-card-subtitle">Platformda kayıtlı Google hesapları ve bağlı YouTube kanalları</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $users->total() }} üye bulundu</span>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>E-Posta</th>
                        <th>Bağlı Kanal</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Kayıt Tarihi</th>
                        <th style="text-align:right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($u->avatar_url)
                                    <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--line-border);">
                                @else
                                    <div style="width:36px;height:36px;border-radius:50%;background:var(--bg-surface-elevated);border:1px solid var(--line-border);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--accent-rust);">
                                        {{ strtoupper(mb_substr($u->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('profile.show', $u->username) }}" target="_blank" style="color:#fff;font-weight:600;font-size:14px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                        {{ $u->name }} <span style="font-size:11px;opacity:0.6;">↗</span>
                                    </a>
                                    <div style="font-size:11px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">{{ '@' . $u->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-main);font-family:'JetBrains Mono',monospace;font-size:12px;max-width:220px;word-break:break-all;">
                            {{ $u->email }}
                        </td>
                        <td>
                            @if($u->activeChannelLink?->channel)
                                <div style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <a href="https://www.youtube.com/{{ $u->activeChannelLink->channel->custom_url ? ltrim($u->activeChannelLink->channel->custom_url, '/') : 'channel/' . $u->activeChannelLink->channel->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" style="color:var(--accent-rust);font-weight:600;text-decoration:underline;">
                                        📺 {{ Str::limit($u->activeChannelLink->channel->title, 20) }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.channels.destroy', $u->activeChannelLink->channel->id) }}" style="display:inline;" onsubmit="return confirm('“{{ addslashes($u->activeChannelLink->channel->title) }}” kanalını ve tüm videolarını silmek istediğinize emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-admin btn-admin-danger" style="padding:2px 7px;font-size:10px;" title="Kanalı Sil">
                                            🗑️ Sil
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span style="color:var(--text-dim);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($u->role === 'admin')
                                <span style="background:rgba(200, 90, 50, 0.2);color:#ff9b73;border:1px solid rgba(200, 90, 50, 0.4);padding:3px 8px;border-radius:6px;font-size:11px;font-family:'JetBrains Mono',monospace;font-weight:700;">YÖNETİCİ</span>
                            @else
                                <span style="background:var(--bg-surface-elevated);color:var(--text-muted);border:1px solid var(--line-subtle);padding:3px 8px;border-radius:6px;font-size:11px;font-family:'JetBrains Mono',monospace;">ÜYE</span>
                            @endif
                        </td>
                        <td>
                            @if($u->status === 'active')
                                <span style="color:var(--status-ok);font-size:12px;font-weight:600;">● Aktif</span>
                            @else
                                <span style="color:var(--status-danger);font-size:12px;font-weight:600;">● Askıda</span>
                            @endif
                        </td>
                        <td style="color:var(--text-muted);font-size:12px;font-family:'JetBrains Mono',monospace;white-space:nowrap;">
                            {{ $u->created_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <div style="display:inline-flex;gap:6px;align-items:center;">
                                <!-- Rol Değiştir -->
                                <form method="POST" action="{{ route('admin.users.toggle_role', $u->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-admin btn-admin-secondary" style="padding:5px 9px;font-size:11px;" title="Rolü değiştir">
                                        {{ $u->role === 'admin' ? 'Üye Yap' : 'Admin Yap' }}
                                    </button>
                                </form>

                                <!-- Askıya Al / Aktif Et -->
                                <form method="POST" action="{{ route('admin.users.toggle_status', $u->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-admin btn-admin-secondary" style="padding:5px 9px;font-size:11px;{{ $u->status === 'active' ? 'color:var(--status-danger);' : 'color:var(--status-ok);' }}" title="Hesap durumunu değiştir">
                                        {{ $u->status === 'active' ? 'Askıya Al' : 'Aktif Et' }}
                                    </button>
                                </form>

                                <!-- Üyeyi Sil (Kalıcı) -->
                                <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" style="display:inline;" onsubmit="return confirm('“{{ addslashes($u->name) }}” ({{ $u->email }}) adlı üyeyi ve tüm platform verilerini KALICI olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz!');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-admin btn-admin-danger" style="padding:5px 9px;font-size:11px;" title="Üyeyi Kalıcı Olarak Sil">
                                        🗑️ Sil
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:32px;text-align:center;color:var(--text-muted);">
                            Aradığınız kriterlere uygun üye bulunamadı.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Sayfalama -->
        @if($users->hasPages())
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-subtle);display:flex;justify-content:center;">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
