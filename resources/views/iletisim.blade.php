@extends('layouts.app')
@section('title', 'İletişim & Destek')
@section('content')
<div style="max-width:960px;margin:0 auto;">
    <p class="eyebrow">Yardım & Çözüm Merkezi</p>
    <h1 class="h1">İletişim & Destek</h1>
    <p class="lead">Görüş, öneri, telif hakları bildirimi veya teknik destek için bize her zaman ulaşabilirsiniz.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;margin-top:28px;">
        
        <!-- Sol Sütun: Doğrudan İletişim Bilgileri -->
        <div>
            <div class="card" style="padding:28px;background:var(--cream);border:1px solid var(--line);border-radius:16px;">
                <h2 style="font:24px 'DM Serif Display',serif;margin:0 0 16px;color:var(--wine);">Bizimle İletişime Geçin</h2>
                <p class="muted" style="line-height:1.6;font-size:14px;margin-bottom:24px;">
                    YouTube Keşif platformu bağımsız içerik üreticilerini desteklemek üzere geliştirilmiştir. Sorularınızı yanıtlamaktan memnuniyet duyarız.
                </p>

                <div style="display:flex;flex-direction:column;gap:18px;">
                    <div style="display:flex;gap:14px;align-items:flex-start;">
                        <span style="font-size:24px;line-height:1;">✉️</span>
                        <div>
                            <div style="font-size:12px;font-weight:700;color:var(--rust);text-transform:uppercase;letter-spacing:0.5px;">Resmi E-Posta</div>
                            <a href="mailto:jackalxxxx4@gmail.com" style="color:var(--wine);font-weight:600;font-size:15px;text-decoration:underline;">
                                jackalxxxx4@gmail.com
                            </a>
                            <div class="muted" style="font-size:12px;margin-top:2px;">7/24 iletebilirsiniz</div>
                        </div>
                    </div>

                    <div style="display:flex;gap:14px;align-items:flex-start;">
                        <span style="font-size:24px;line-height:1;">⚡</span>
                        <div>
                            <div style="font-size:12px;font-weight:700;color:var(--rust);text-transform:uppercase;letter-spacing:0.5px;">Yanıt Süresi</div>
                            <strong style="color:var(--wine);font-size:15px;">24 Saat İçinde</strong>
                            <div class="muted" style="font-size:12px;margin-top:2px;">Tüm talepler titizlikle incelenir</div>
                        </div>
                    </div>

                    <div style="display:flex;gap:14px;align-items:flex-start;">
                        <span style="font-size:24px;line-height:1;">🌐</span>
                        <div>
                            <div style="font-size:12px;font-weight:700;color:var(--rust);text-transform:uppercase;letter-spacing:0.5px;">Ekosistem</div>
                            <a href="https://aiajan.com.tr" target="_blank" style="color:var(--wine);font-weight:600;font-size:15px;text-decoration:underline;">
                                aiajan.com.tr ↗
                            </a>
                            <div class="muted" style="font-size:12px;margin-top:2px;">Yapay zeka ve bağımsız üretici ağı</div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--line);">
                    <div class="eyebrow" style="margin-bottom:6px;">Hızlı Yardım Konuları</div>
                    <ul style="padding-left:18px;margin:0;font-size:13px;line-height:1.7;" class="muted">
                        <li>Kanal değiştirme veya doğrulama sorunları</li>
                        <li>Video vitrini senkronizasyon talepleri</li>
                        <li>Telif hakkı / İçerik kaldırma bildirimleri</li>
                        <li>Topluluk kuralları ihlalleri</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sağ Sütun: Güvenli İletişim Formu -->
        <div>
            <div class="card" style="padding:28px;background:var(--cream);border:1px solid var(--line);border-radius:16px;">
                <h2 style="font:24px 'DM Serif Display',serif;margin:0 0 8px;color:var(--wine);">Mesaj Gönderin</h2>
                <p class="muted" style="font-size:13px;margin-bottom:20px;">
                    Formu doldurarak bize doğrudan ulaşabilirsiniz. Lütfen geçerli bir e-posta adresi girin.
                </p>

                <form method="POST" action="{{ route('contact.send') }}">
                    @csrf

                    <!-- Spam Bot Honeypot (Görünmez alan) -->
                    <div style="display:none;" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label for="name" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Adınız Soyadınız <span style="color:var(--rust);">*</span></label>
                        <input type="text" class="field" id="name" name="name" value="{{ old('name', $user?->name) }}" required placeholder="Örn: Ahmet Yılmaz">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label for="email" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">E-Posta Adresiniz <span style="color:var(--rust);">*</span></label>
                        <input type="email" class="field" id="email" name="email" value="{{ old('email', $user?->email) }}" required placeholder="ornek@alanadi.com">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label for="subject" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Konu <span style="color:var(--rust);">*</span></label>
                        <select class="field" id="subject" name="subject" required style="cursor:pointer;">
                            <option value="destek" @selected(old('subject') === 'destek')>Teknik Destek & Yardım</option>
                            <option value="oneri" @selected(old('subject') === 'oneri')>Öneri & Geri Bildirim</option>
                            <option value="telif" @selected(old('subject') === 'telif')>Telif Hakkı / Kanal Kaldırma Talebi</option>
                            <option value="isbirligi" @selected(old('subject') === 'isbirligi')>İş Birliği & Ortaklık</option>
                            <option value="diger" @selected(old('subject') === 'diger')>Diğer</option>
                        </select>
                    </div>

                    <div style="margin-bottom:20px;">
                        <label for="message" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Mesajınız <span style="color:var(--rust);">*</span></label>
                        <textarea class="field" id="message" name="message" rows="5" required placeholder="Mesajınızı detaylı şekilde buraya yazabilirsiniz..." style="resize:vertical;">{{ old('message') }}</textarea>
                    </div>

                    <button class="btn" type="submit" style="width:100%;padding:14px;font-size:14px;font-weight:700;">
                        <span>✉️ Mesajı Güvenle Gönder</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

