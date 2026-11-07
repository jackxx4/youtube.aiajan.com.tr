@extends('layouts.app')
@section('title', 'Kanal & Üretici Keşfi')
@section('content')
<div style="max-width:960px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px;">
        <div>
            <p class="eyebrow">{{ $pool === 'supporter' ? '⭐ Destekçi Üretici Havuzu' : '📺 Topluluk Kanal Havuzu' }}</p>
            <h1 class="h1" style="margin:4px 0 6px;">Yeni Üretici Kanallarıyla Tanış</h1>
            <p class="lead" style="margin-bottom:0;">
                Aşağıdaki listeden <strong>tam 3 kanalı</strong> seçerek platform içi takibe alabilirsiniz. Bu işlem YouTube aboneliği oluşturmaz, platform içi üretici dayanışmasını güçlendirir.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn secondary" style="padding:10px 16px;font-size:13px;">
            ← Genel Bakış'a Dön
        </a>
    </div>

    @php
        $displayItems = isset($channels) && $channels->isNotEmpty() ? $channels : (isset($users) ? $users : collect());
    @endphp

    @if($displayItems->isEmpty())
        <div class="card" style="margin-top:24px;text-align:center;padding:48px 24px;">
            <div style="font-size:48px;margin-bottom:12px;">🌟</div>
            <h2 style="font:24px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Şu An Gösterilecek Yeni Kanal Bulunmuyor</h2>
            <p class="muted" style="max-width:480px;margin:0 auto 20px;font-size:14px;line-height:1.6;">
                Havuzdaki tüm kanalları incelediniz veya günlük limitinize ulaştınız. Yeni üreticiler katıldıkça burada listelenecektir.
            </p>
            <a href="{{ route('dashboard') }}" class="btn" style="padding:10px 20px;">
                Genel Bakış'a Dön
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('discovery.follow', $pool) }}" class="card" style="margin-top:24px;padding:24px;">
            @csrf

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;border-bottom:1px solid var(--line);padding-bottom:14px;">
                <span class="muted" style="font-size:13px;">
                    Listeden tam 3 kanal seçin:
                </span>
                <span id="choice-count" style="font-weight:700;font-size:13px;color:var(--rust);background:var(--sand);padding:4px 12px;border-radius:12px;">
                    0 / 3 seçildi
                </span>
            </div>

            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-top:0;">
                @foreach($displayItems as $item)
                    @php
                        // Kanal nesnesi mi yoksa kullanıcı nesnesi mi?
                        $ch = ($item instanceof \App\Models\YouTubeChannel) ? $item : ($item->activeChannelLink?->channel ?? null);
                        $itemId = $item->id;
                        $title = $ch?->title ?? $item->name ?? 'YouTube Kanalı';
                        $avatar = $ch?->thumbnail_url ?: ($item->avatar_url ?? null);
                        $handle = $ch?->custom_url ? ('@' . ltrim($ch->custom_url, '@')) : (isset($item->username) ? ('@' . $item->username) : '');
                        $channelUrl = $ch ? ($ch->custom_url ? 'https://www.youtube.com/' . ltrim($ch->custom_url, '/') : 'https://www.youtube.com/channel/' . $ch->youtube_channel_id) : '#';
                    @endphp
                    <label class="card check channel-select-card" style="cursor:pointer;margin:0;padding:14px;display:flex;align-items:center;gap:12px;border:1px solid var(--line);border-radius:12px;transition:all 0.15s ease;">
                        <input type="checkbox" name="channel_ids[]" value="{{ $itemId }}" class="person-choice" style="width:20px;height:20px;accent-color:var(--rust);cursor:pointer;flex-shrink:0;">
                        
                        <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--sand);border:2px solid var(--line);display:flex;align-items:center;justify-content:center;">
                            @if($avatar)
                                <img src="{{ $avatar }}" alt="{{ $title }}" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span style="font-weight:700;color:var(--wine);font-size:16px;">▶</span>
                            @endif
                        </div>

                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $title }}
                            </div>
                            @if($handle)
                                <div class="muted" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $handle }}
                                </div>
                            @endif
                            <div style="margin-top:2px;">
                                <a href="{{ $channelUrl }}" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();" style="font-size:11px;color:var(--rust);text-decoration:underline;">
                                    YouTube Kanalı ↗
                                </a>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-top:24px;padding-top:18px;border-top:1px solid var(--line);">
                <button type="submit" class="btn" id="follow-submit" disabled style="padding:12px 24px;font-size:14px;">
                    Seçilen 3 Kanalı Takip Et
                </button>
                <span class="muted" style="font-size:12px;">
                    Günlük 3 abone olma / takip hakkınız bulunmaktadır.
                </span>
            </div>
        </form>

        <script>
            (function() {
                const boxes = Array.from(document.querySelectorAll('.person-choice'));
                const button = document.getElementById('follow-submit');
                const counter = document.getElementById('choice-count');

                function refresh() {
                    const checkedCount = boxes.filter(x => x.checked).length;
                    if (counter) {
                        counter.textContent = checkedCount + ' / 3 seçildi';
                    }
                    if (button) {
                        button.disabled = checkedCount !== 3;
                    }
                    boxes.forEach(box => {
                        const card = box.closest('.channel-select-card');
                        if (box.checked) {
                            if (card) {
                                card.style.borderColor = 'var(--rust)';
                                card.style.background = '#fcf8f5';
                            }
                        } else {
                            box.disabled = (checkedCount >= 3);
                            if (card) {
                                card.style.borderColor = 'var(--line)';
                                card.style.background = '';
                            }
                        }
                    });
                }

                boxes.forEach(box => box.addEventListener('change', refresh));
                refresh();
            })();
        </script>
    @endif
</div>
@endsection
