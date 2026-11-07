<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFollow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'follower_user_id',
        'followed_user_id',
        'youtube_channel_id',
        'pool_type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_user_id');
    }

    public function followed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followed_user_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(YouTubeChannel::class, 'youtube_channel_id');
    }
}
