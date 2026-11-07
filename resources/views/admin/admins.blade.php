@extends('layouts.admin')
@section('title', 'Yönetici Hesapları · Yönetim Merkezi')

@section('content')
<div>
    <!-- Başlık Bölümü -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                GÜVENLİK · YÖNETİCİ HESAPLARI
            </div>
            <h1 style="font:32px 'DM Serif Display',serif;color:#fff;margin:0 0 6px;">Yönetici Yönetimi</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:680px;">
                Platform yöneticileri normal üyelerden tamamen bağımsız <code>admins</code> tablosunda tutulmaktadır. Buradan yeni yönetici ekleyebilir, rolleri ve erişimleri yönetebilirsiniz.
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.dashboard') }}" class="btn-admin btn-admin-secondary">
                <span>← Genel Bakış</span>
            </a>
        </div>
    </div>

    <!-- Yeni Yönetici Ekleme Kartı (Form) -->
    <div class="ui-card" style="margin-bottom:28px;">
        <div class="ui-card-header">
            <div>
                <h2 class="ui-card-title">➕ Yeni Yönetici Hesabı Oluştur</h2>
                <div class="ui-card-subtitle">Yönetim konsoluna erişebilecek yeni bir yönetici yetkilendirin</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.admins.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">İsim Soyisim</label>
                    <input type="text" name="name" class="admin-input" placeholder="Örn: Ahmet Yılmaz" required value="{{ old('name') }}">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">Kullanıcı Adı</label>
                    <input type="text" name="username" class="admin-input" placeholder="Örn: ahmet" required value="{{ old('username') }}">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">E-Posta Adresi</label>
                    <input type="email" name="email" class="admin-input" placeholder="Örn: ahmet@aiajan.com.tr" required value="{{ old('email') }}">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">Yönetici Parolası</label>
                    <input type="password" name="password" class="admin-input" placeholder="En az 6 karakter" required>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">Yönetim Rolü</label>
                    <select name="role" class="admin-input">
                        <option value="super_admin">Süper Yönetici (Tam Yetkili)</option>
                        <option value="admin">Yönetici (Standart)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-admin-primary" style="padding:11px 22px;">
                🛡️ Yöneticiyi Kaydet
            </button>
        </form>
    </div>

    <!-- Mevcut Yöneticiler Tablosu -->
    <div class="ui-card">
        <div class="ui-card-header">
            <div>
                <h2 class="ui-card-title">Mevcut Sistem Yöneticileri</h2>
                <div class="ui-card-subtitle">Yönetim merkezine giriş yapma yetkisine sahip hesaplar</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $admins->count() }} yönetici</span>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Yönetici</th>
                        <th>Kullanıcı Adı</th>
                        <th>E-Posta</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Son Giriş</th>
                        <th style="text-align:right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admins as $adm)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:36px;height:36px;border-radius:8px;background:var(--bg-surface-elevated);border:1px solid var(--line-border);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--accent-rust);">
                                    {{ strtoupper(mb_substr($adm->name, 0, 1)) }}
                                </div>
                                <div>
                                    <strong style="color:#fff;font-size:14px;">{{ $adm->name }}</strong>
                                    @if($currentAdmin && $currentAdmin->id === $adm->id)
                                        <span style="font-size:11px;color:var(--accent-rust);font-weight:600;margin-left:4px;">(Siz)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-main);font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;">
                            {{ $adm->username ?: '—' }}
                        </td>
                        <td style="color:var(--text-muted);font-family:'JetBrains Mono',monospace;font-size:12px;">
                            {{ $adm->email }}
                        </td>
                        <td>
                            @if($adm->role === 'super_admin')
                                <span style="background:rgba(200, 90, 50, 0.2);color:#ff9b73;border:1px solid rgba(200, 90, 50, 0.4);padding:3px 8px;border-radius:6px;font-size:11px;font-family:'JetBrains Mono',monospace;font-weight:700;">
                                    SÜPER YÖNETİCİ
                                </span>
                            @else
                                <span style="background:var(--bg-surface-elevated);color:var(--text-muted);border:1px solid var(--line-subtle);padding:3px 8px;border-radius:6px;font-size:11px;font-family:'JetBrains Mono',monospace;">
                                    YÖNETİCİ
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($adm->is_active)
                                <span style="color:var(--status-ok);font-size:12px;font-weight:600;">● Aktif</span>
                            @else
                                <span style="color:var(--status-danger);font-size:12px;font-weight:600;">● Askıda</span>
                            @endif
                        </td>
                        <td style="color:var(--text-muted);font-size:12px;font-family:'JetBrains Mono',monospace;white-space:nowrap;">
                            {{ $adm->last_login_at ? \Carbon\Carbon::parse($adm->last_login_at)->diffForHumans() : 'Henüz giriş yapmadı' }}
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            @if($currentAdmin && $currentAdmin->id === $adm->id)
                                <span style="color:var(--text-dim);font-size:12px;">(Kendi Hesabınız)</span>
                            @else
                                <div style="display:inline-flex;gap:6px;align-items:center;">
                                    <form method="POST" action="{{ route('admin.admins.toggle_status', $adm->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-admin btn-admin-secondary" style="padding:5px 9px;font-size:11px;{{ $adm->is_active ? 'color:var(--status-danger);' : 'color:var(--status-ok);' }}" title="Durumu değiştir">
                                            {{ $adm->is_active ? 'Askıya Al' : 'Aktif Et' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.admins.destroy', $adm->id) }}" style="display:inline;" onsubmit="return confirm('“{{ addslashes($adm->name) }}” adlı yöneticiyi silmek istediğinize emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-admin btn-admin-danger" style="padding:5px 9px;font-size:11px;" title="Yöneticiyi Sil">
                                            🗑️ Sil
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

