<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouTubeOAuthToken extends Model
{
    protected $table = 'youtube_oauth_tokens';
    protected $fillable = ['user_id', 'access_token_enc', 'refresh_token_enc', 'expires_at', 'scopes'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
