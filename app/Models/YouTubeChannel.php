<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YouTubeChannel extends Model
{
    protected $table = 'youtube_channels';
    protected $fillable = ['youtube_channel_id', 'title', 'thumbnail_url', 'custom_url', 'subscriber_count', 'view_count', 'last_synced_at'];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'youtube_channel_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(UserChannelLink::class, 'youtube_channel_id');
    }
}
