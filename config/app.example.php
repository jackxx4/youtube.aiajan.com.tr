<?php
declare(strict_types=1);

/**
 * Copy to config/app.php on the server.
 * Never commit real credentials or encryption keys.
 */

return [
    'app_url' => getenv('APP_URL') ?: 'https://youtube.aiajan.com.tr',
    'environment' => getenv('APP_ENV') ?: 'production',
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI')
            ?: 'https://youtube.aiajan.com.tr/callback.php',
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: '',
        'user' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    // Generate a random 32-byte key and store it outside the repository.
    'token_encryption_key' => getenv('TOKEN_ENCRYPTION_KEY') ?: '',
];
