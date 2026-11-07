<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVideoSelection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'video_id',
        'youtube_channel_id',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(YouTubeChannel::class, 'youtube_channel_id');
    }
}
