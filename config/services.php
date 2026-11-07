<?php

return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'resend' => ['key' => env('RESEND_KEY')],
    'ses' => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'slack' => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', 'BURAYA_YAZILACAK'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', 'BURAYA_YAZILACAK'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],
    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY', 'AIzaSy_REDACTED_LEAKED_KEY'),
    ],
    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY', 'AIzaSyASQDjjn7zkLDjDmKkPwXAzMoK7fZdoFYA'),
        'project_id' => env('FIREBASE_PROJECT_ID', 'aiajan-portfolio'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'aiajan-portfolio.firebaseapp.com'),
    ],
];
