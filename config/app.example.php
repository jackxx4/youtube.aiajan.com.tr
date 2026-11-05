<?php
declare(strict_types=1);

/**
 * Örnek yapılandırma.
 * Sunucuda config/app.php adıyla kopyalayın.
 * BURAYA_YAZILACAK değerleri gerçek bilgilerle değiştirmeden uygulamayı çalıştırmayın.
 * Gerçek kimlik bilgilerini GitHub'a kesinlikle göndermeyin.
 */

$placeholder = 'BURAYA_YAZILACAK';

return [
    'app_url' => getenv('APP_URL') ?: 'https://youtube.aiajan.com.tr',
    'environment' => getenv('APP_ENV') ?: 'production',

    'google' => [
        // Google Cloud Console > APIs & Services > Credentials
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: $placeholder,
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: $placeholder,
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI')
            ?: 'https://youtube.aiajan.com.tr/callback.php',
    ],

    'youtube' => [
        // YouTube Data API v3 API key. OAuth token gereken kullanıcı işlemlerinin yerine geçmez.
        'api_key' => getenv('YOUTUBE_API_KEY') ?: $placeholder,
    ],

    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: $placeholder,
        'user' => getenv('DB_USER') ?: $placeholder,
        'password' => getenv('DB_PASSWORD') ?: $placeholder,
        'charset' => 'utf8mb4',
    ],

    // Sunucuda üretilecek rastgele 32 byte anahtar.
    'token_encryption_key' => getenv('TOKEN_ENCRYPTION_KEY') ?: $placeholder,

    'contribution' => [
        // Ödeme/katkı sağlayıcısı henüz belirlenmedi.
        'provider' => getenv('CONTRIBUTION_PROVIDER') ?: $placeholder,
        'api_key' => getenv('CONTRIBUTION_API_KEY') ?: $placeholder,
        'api_secret' => getenv('CONTRIBUTION_API_SECRET') ?: $placeholder,
        'webhook_secret' => getenv('CONTRIBUTION_WEBHOOK_SECRET') ?: $placeholder,
    ],
];
