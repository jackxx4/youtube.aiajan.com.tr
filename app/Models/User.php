<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'google_sub',
        'name',
        'email',
        'username',
        'avatar_url',
        'google_channel_ids',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_channel_ids' => 'array',
        ];
    }

    public function activeChannelLink(): HasOne
    {
        return $this->hasOne(UserChannelLink::class)
            ->whereNull('ended_at')
            ->latestOfMany('id');
    }

    public function channelLinks(): HasMany
    {
        return $this->hasMany(UserChannelLink::class);
    }

    public function oauthToken(): HasOne
    {
        return $this->hasOne(YouTubeOAuthToken::class);
    }

    public function supporterStatuses(): HasMany
    {
        return $this->hasMany(SupporterStatus::class);
    }

    public function follows(): HasMany
    {
        return $this->hasMany(PlatformFollow::class, 'follower_user_id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(PlatformFollow::class, 'followed_user_id');
    }

    public function videoSelections(): HasMany
    {
        return $this->hasMany(UserVideoSelection::class);
    }

    public function isSupporter(): bool
    {
        return $this->supporterStatuses()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getActiveChannelAttribute(): ?YouTubeChannel
    {
        return $this->activeChannelLink?->channel;
    }
}
