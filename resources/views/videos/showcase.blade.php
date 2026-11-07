@extends('layouts.app')
@section('title', 'Video Vitrinim')
@section('content')
<div style="max-width:960px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px;">
        <div>
            <p class="eyebrow">Kanal Yönetimi</p>
            <h1 class="h1" style="margin:4px 0 6px;">Video Vitrinim</h1>
            <p class="lead" style="margin-bottom:0;">
                Vitrininiz, topluluk keşif akışında ve profilinizde öne çıkan <strong>en fazla 3 videonuzdan</strong> oluşur.
            </p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn secondary" style="padding:10px 16px;font-size:13px;">
                ← Genel Bakış'a Dön
            </a>
        </div>
    </div>

    <!-- Bağlı Kanal Bilgi Şeridi -->
    <div class="card" style="margin:20px 0 28px;padding:14px 20px;background:#fbf7f2;border-left:4px solid var(--rust);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;">
        <div style="display:flex;align-items:center;gap:12px;">
            @if($channel->thumbnail_url)
                <img src="{{ $channel->thumbnail_url }}" alt="{{ $channel->title }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
            @else
                <div style="width:36px;height:36px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);">
                    {{ mb_substr($channel->title, 0, 1) }}
                </div>
            @endif
            <div>
                <strong>Bağlı Kanal:</strong> {{ $channel->title }}
                <span class="muted" style="margin-left:6px;">({{ $channel->custom_url ?: ('ID: ' . $channel->youtube_channel_id) }})</span>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <span style="font-weight:700;color:var(--wine);background:var(--sand);padding:4px 10px;border-radius:12px;font-size:12px;">
                {{ $showcaseVideos->count() }} / 3 Slot Dolu
            </span>
            @if($showcaseVideos->isNotEmpty())
                <form method="POST" action="{{ route('videos.showcase.clear') }}" onsubmit="return confirm('Vitrininizdeki tüm videoları kaldırmak istediğinize emin misiniz?');" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:transparent;border:none;color:#c93b2b;font-size:12px;text-decoration:underline;cursor:pointer;padding:4px 0;">
                        🗑️ Tümünü Temizle
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- 3 VİTRİN SLOTU (KARTLAR) -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <h2 style="font:22px 'DM Serif Display',serif;margin:0;color:var(--wine);">Vitrin Slotları (Maks. 3 Video)</h2>
        <span class="muted" style="font-size:13px;">Toplulukta bu 3 video öne çıkarılır</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:20px;margin-bottom:36px;">
        @for($slot = 0; $slot < 3; $slot++)
            @php $v = $showcaseVideos->get($slot); @endphp
            @if($v)
                <!-- Dolu Slot -->
                <div class="card" style="padding:0;overflow:hidden;border:2px solid var(--rust);border-radius:14px;display:flex;flex-direction:column;box-shadow:0 4px 16px rgba(173,101,72,0.12);">
                    <div style="position:relative;width:100%;aspect-ratio:16/9;background:#1a1412;overflow:hidden;">
                        @if($v->thumbnail_url)
                            <img src="{{ $v->thumbnail_url }}" alt="{{ $v->title }}" style="width:100%;height:100%;object-fit:cover;display:block;">
                        @else
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;">▶</div>
                        @endif
                        <span style="position:absolute;top:10px;left:10px;background:var(--rust);color:#fff;font-size:11px;font-weight:700;padding:4px 8px;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.3);">
                            ⭐ Slot {{ $slot + 1 }} · Yayında
                        </span>
                    </div>

                    <div style="padding:16px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                        <div>
                            <h3 style="font-size:14px;font-weight:600;line-height:1.45;color:var(--wine);margin:0 0 8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" title="{{ $v->title }}">
                                {{ $v->title }}
                            </h3>
                            <div class="muted" style="font-size:12px;margin-bottom:14px;">
                                YouTube ID: <code>{{ $v->youtube_video_id }}</code>
                            </div>
                        </div>

                        <div style="display:flex;gap:8px;align-items:center;padding-top:12px;border-top:1px solid var(--line);">
                            <a href="https://www.youtube.com/watch?v={{ $v->youtube_video_id }}" target="_blank" rel="noopener noreferrer" class="btn secondary" style="flex:1;padding:8px 10px;font-size:12px;text-align:center;">
                                YouTube'da Aç ↗
                            </a>
                            <form method="POST" action="{{ route('videos.showcase.remove', $v->id) }}" onsubmit="return confirm('“{{ addslashes($v->title) }}” videosunu vitrinden kaldırmak istediğinize emin misiniz?');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn secondary" style="padding:8px 12px;font-size:12px;color:#c93b2b;background:#fff5f3;border-color:#f5c6cb;cursor:pointer;" title="Vitrinden Kaldır">
                                    ✕ Kaldır
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <!-- Boş Slot -->
                <div class="card" style="border:2px dashed #d6bca8;border-radius:14px;background:#fcfbf8;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:36px 20px;min-height:260px;">
                    <div style="width:48px;height:48px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--muted);margin-bottom:12px;">
                        ＋
                    </div>
                    <div style="font-weight:700;font-size:15px;color:var(--wine);margin-bottom:4px;">
                        Slot {{ $slot + 1 }} Boş
                    </div>
                    <p class="muted" style="font-size:12px;margin:0;max-width:220px;line-height:1.5;">
                        Aşağıdaki formdan video bağlantısı ekleyerek bu slotu doldurun.
                    </p>
                </div>
            @endif
        @endfor
    </div>

    <!-- VİDEO EKLEME FORMU -->
    <div class="card" style="padding:26px 30px;background:var(--cream);border:1px solid var(--line);border-radius:16px;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div style="font-size:32px;line-height:1;">🔗</div>
            <div style="flex:1;">
                <div class="eyebrow" style="color:var(--rust);">Vitrine Yeni Video Ekle</div>
                <h3 style="font:22px 'DM Serif Display',serif;margin:4px 0 8px;color:var(--wine);">YouTube Video Bağlantısı ile Ekle</h3>
                <p class="muted" style="margin-bottom:18px;font-size:13px;line-height:1.6;">
                    Bağlı kanalınıza (<strong>{{ $channel->title }}</strong>) ait öne çıkarmak istediğiniz videonun bağlantısını yapıştırın. Sistem videonun kanalınıza ait olduğunu doğrulayacak ve vitrininize yerleştirecektir.
                </p>

                @if($showcaseVideos->count() >= 3)
                    <div class="alert" style="margin:0 0 16px;background:#fff8e1;color:#8d6e63;border:1px solid #ffe082;font-size:13px;">
                        ⚠️ Vitrininiz tamamen dolu (3/3). Yeni bir video eklemek için önce yukarıdaki slotlardan birini <strong>“✕ Kaldır”</strong> butonuyla boşaltmalısınız.
                    </div>
                @endif

                <form method="POST" action="{{ route('videos.showcase.add_url') }}" style="display:flex;gap:10px;flex-wrap:wrap;">
                    @csrf
                    <input type="text"
                        name="video_url"
                        class="field"
                        placeholder="Örn: https://www.youtube.com/watch?v=..."
                        value="{{ old('video_url') }}"
                        required
                        {{ $showcaseVideos->count() >= 3 ? 'disabled' : '' }}
                        style="flex:1;min-width:260px;margin:0;">
                    <button type="submit"
                        class="btn"
                        {{ $showcaseVideos->count() >= 3 ? 'disabled' : '' }}
                        style="white-space:nowrap;padding:12px 24px;font-size:14px;">
                        <span>＋ Vitrine Ekle</span>
                    </button>
                </form>

                <div style="margin-top:12px;font-size:12px;color:var(--muted);display:flex;gap:12px;flex-wrap:wrap;">
                    <span>✓ Normal (yatay) videolar kabul edilir</span>
                    <span>•</span>
                    <span>✗ YouTube Shorts videoları kabul edilmez</span>
                    <span>•</span>
                    <span>✓ En fazla 3 video sergilenebilir</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
