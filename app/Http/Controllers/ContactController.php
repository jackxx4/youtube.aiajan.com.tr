<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        return view('iletisim', compact('user'));
    }

    public function send(Request $request): RedirectResponse
    {
        // Bot Tuzağı (Honeypot) - Eğer doldurulmuşsa bot olarak algılanıp sessizce başarılı dönülür
        if ($request->filled('website')) {
            return redirect()->route('contact')
                ->with('status', 'Mesajınız başarıyla iletildi. En kısa sürede geri dönüş yapılacaktır.');
        }

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'in:destek,oneri,telif,isbirligi,diger'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'name.required'    => 'Lütfen adınızı ve soyadınızı belirtin.',
            'email.required'   => 'Lütfen geçerli bir e-posta adresi girin.',
            'email.email'      => 'Girdiğiniz e-posta adresi geçersizdir.',
            'subject.required' => 'Lütfen bir iletişim konusu seçin.',
            'subject.in'       => 'Geçersiz bir konu seçimi yaptınız.',
            'message.required' => 'Lütfen iletmek istediğiniz mesajınızı yazın.',
            'message.min'      => 'Mesajınız en az 10 karakterden oluşmalıdır.',
            'message.max'      => 'Mesajınız en fazla 3000 karakter olabilir.',
        ]);

        $ipHash = hash('sha256', (string) $request->ip());
        $user = $request->user();

        // 1. Audit Log tablosuna güvenli ve anonimleştirilmiş kayıt
        try {
            DB::table('audit_logs')->insert([
                'actor_user_id' => $user?->id,
                'action'        => 'contact_form_submitted',
                'entity_type'   => 'ContactMessage',
                'entity_id'     => null,
                'metadata'      => json_encode([
                    'name'    => strip_tags($validated['name']),
                    'email'   => $validated['email'],
                    'subject' => $validated['subject'],
                    'message' => strip_tags($validated['message']),
                ], JSON_UNESCAPED_UNICODE),
                'ip_hash'       => $ipHash,
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('İletişim mesajı audit_logs tablosuna yazılamadı: ' . $e->getMessage());
        }

        // 2. Sistem günlüğüne bildirim
        Log::info("Yeni İletişim Talebi: [{$validated['subject']}] - {$validated['email']} ({$validated['name']})");

        return redirect()->route('contact')
            ->with('status', 'Mesajınız başarıyla iletildi! Ekibimiz genellikle 24 saat içinde e-posta adresiniz üzerinden geri dönüş yapmaktadır.');
    }
}

