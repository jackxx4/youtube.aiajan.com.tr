@extends('layouts.admin')
@section('title', 'İletişim ve Destek Talepleri · Yönetim Merkezi')

@section('content')
<div>
    <!-- Başlık Bölümü -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <div>
            <div style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent-rust);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                GERİ BİLDİRİM & DESTEK
            </div>
            <h1 style="font:28px 'DM Serif Display',serif;color:#fff;margin:0 0 4px;">İletişim Talepleri</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:680px;">
                Kullanıcıların ve ziyaretçilerin platform iletişim formu üzerinden ilettiği destek ve geri bildirim mesajları.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.dashboard') }}" class="btn-admin btn-admin-secondary">
                <span>← Genel Bakış</span>
            </a>
        </div>
    </div>

    <!-- Mesajlar Listesi Kartı -->
    <div class="ui-card">
        <div class="ui-card-header">
            <div>
                <h2 class="ui-card-title">Gelen Mesajlar</h2>
                <div class="ui-card-subtitle">İletişim formundan kaydedilen en son mesajlar</div>
            </div>
            <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;">Toplam {{ $totalMessages }} mesaj</span>
        </div>

        @if($parsedMessages->isEmpty())
            <div style="padding:36px;text-align:center;color:var(--text-muted);font-size:13px;background:var(--bg-surface-elevated);border-radius:8px;">
                Henüz iletişim formundan iletilmiş bir mesaj bulunmuyor.
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($parsedMessages as $msg)
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

            @if($parsedMessages->hasPages())
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-subtle);display:flex;justify-content:center;">
                    {{ $parsedMessages->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

