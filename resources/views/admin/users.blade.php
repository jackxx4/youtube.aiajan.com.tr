@extends('layouts.app')
@section('title', 'Kullanıcı Yönetimi · Yönetim Merkezi')
@section('content')
<div style="max-width:1160px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
        <div>
            <div class="eyebrow" style="color:var(--rust);">Yönetici Kontrol Merkezi</div>
            <h1 class="h1" style="margin:4px 0 6px;">Kullanıcı Yönetimi</h1>
            <p class="lead" style="margin-bottom:0;">Platforma kayıtlı üyeleri görüntüleyin, rollerini ve durumlarını yönetin veya kalıcı olarak silin.</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="{{ route('admin.dashboard') }}" class="btn secondary" style="padding:10px 16px;font-size:13px;">
                <span>← Yönetim Genel Bakış</span>
            </a>
            <a href="{{ route('dashboard') }}" class="btn" style="padding:10px 16px;font-size:13px;">
                <span>Panoya Dön</span>
            </a>
        </div>
    </div>

    <!-- Yönetim Sekmeleri (Tabs) -->
    <div style="display:flex;gap:8px;border-bottom:2px solid var(--line);margin:20px 0 24px;">
        <a href="{{ route('admin.dashboard') }}" style="padding:10px 18px;font-weight:600;font-size:14px;color:var(--muted);border-bottom:3px solid transparent;margin-bottom:-2px;text-decoration:none;">
            📊 Genel Bakış
        </a>
        <a href="{{ route('admin.users') }}" style="padding:10px 18px;font-weight:700;font-size:14px;color:var(--wine);border-bottom:3px solid var(--wine);margin-bottom:-2px;text-decoration:none;">
            👥 Üye Yönetimi ({{ $totalUsers }})
        </a>
    </div>

    <!-- Arama ve Filtreleme Kartı -->
    <div class="card" style="padding:18px 22px;margin-bottom:24px;">
        <form method="GET" action="{{ route('admin.users') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="flex:2;min-width:220px;">
                <input type="text" name="search" value="{{ $search }}" class="field" placeholder="İsim, kullanıcı adı veya e-posta ile ara..." style="margin:0;">
            </div>
            <div style="min-width:140px;">
                <select name="role" class="field" style="margin:0;">
                    <option value="">Tüm Roller</option>
                    <option value="user" {{ $role === 'user' ? 'selected' : '' }}>Üye</option>
                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Yönetici</option>
                </select>
            </div>
            <div style="min-width:140px;">
                <select name="status" class="field" style="margin:0;">
                    <option value="">Tüm Durumlar</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Askıda</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn" style="padding:11px 18px;font-size:13px;">Filtrele</button>
                @if($search !== '' || $role || $status)
                    <a href="{{ route('admin.users') }}" class="btn secondary" style="padding:11px 16px;font-size:13px;">Temizle</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Kullanıcılar Tablosu -->
    <div class="card" style="padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
            <h2 style="font:22px 'DM Serif Display',serif;margin:0;color:var(--wine);">
                Kayıtlı Üyeler
                @if($search !== '')
                    <span style="font-size:14px;color:var(--muted);font-weight:400;">("{{ $search }}" için sonuçlar)</span>
                @endif
            </h2>
            <span class="muted" style="font-size:13px;">Toplam {{ $users->total() }} üye bulundu</span>
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
                        <th style="padding:10px 8px;text-align:right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr style="border-bottom:1px solid var(--line);">
                        <td style="padding:12px 8px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                @if($u->avatar_url)
                                    <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                @else
                                    <div style="width:36px;height:36px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);">
                                        {{ strtoupper(mb_substr($u->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('profile.show', $u->username) }}" target="_blank" style="color:var(--wine);font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                        {{ $u->name }} <span style="font-size:11px;opacity:0.7;">↗</span>
                                    </a>
                                    <div class="muted" style="font-size:11px;">{{ '@' . $u->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px 8px;color:var(--ink);max-width:240px;word-break:break-all;">{{ $u->email }}</td>
                        <td style="padding:12px 8px;">
                            @if($u->activeChannelLink?->channel)
                                <a href="https://www.youtube.com/{{ $u->activeChannelLink->channel->custom_url ? ltrim($u->activeChannelLink->channel->custom_url, '/') : 'channel/' . $u->activeChannelLink->channel->youtube_channel_id }}" target="_blank" rel="noopener noreferrer" style="color:var(--rust);font-weight:600;text-decoration:underline;">
                                    📺 {{ $u->activeChannelLink->channel->title }}
                                </a>
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
                        <td style="padding:12px 8px;color:var(--muted);font-size:12px;white-space:nowrap;">
                            {{ $u->created_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td style="padding:12px 8px;text-align:right;white-space:nowrap;">
                            @if(auth()->id() === $u->id)
                                <span class="muted" style="font-size:12px;font-weight:600;">(Siz)</span>
                            @else
                                <div style="display:inline-flex;gap:6px;align-items:center;">
                                    <!-- Rol Değiştir -->
                                    <form method="POST" action="{{ route('admin.users.toggle_role', $u->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn secondary" style="padding:5px 9px;font-size:11px;" title="Rolü değiştir">
                                            {{ $u->role === 'admin' ? 'Üye Yap' : 'Admin Yap' }}
                                        </button>
                                    </form>

                                    <!-- Askıya Al / Aktif Et -->
                                    <form method="POST" action="{{ route('admin.users.toggle_status', $u->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn secondary" style="padding:5px 9px;font-size:11px;{{ $u->status === 'active' ? 'color:#c93b2b;border-color:#f5c6cb;' : 'color:#2d6a3f;border-color:#c3e6cb;' }}" title="Hesap durumunu değiştir">
                                            {{ $u->status === 'active' ? 'Askıya Al' : 'Aktif Et' }}
                                        </button>
                                    </form>

                                    <!-- Üyeyi Sil (Kalıcı) -->
                                    <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" style="display:inline;" onsubmit="return confirm('“{{ addslashes($u->name) }}” ({{ $u->email }}) adlı üyeyi ve tüm platform verilerini KALICI olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn secondary" style="padding:5px 9px;font-size:11px;color:#fff;background:#c93b2b;border-color:#c93b2b;cursor:pointer;" title="Üyeyi Kalıcı Olarak Sil">
                                            🗑️ Sil
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:32px;text-align:center;color:var(--muted);">
                            Aradığınız kriterlere uygun üye bulunamadı.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Sayfalama -->
        @if($users->hasPages())
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line);display:flex;justify-content:center;">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

