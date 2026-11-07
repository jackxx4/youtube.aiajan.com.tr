<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformFollow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'follower_user_id',
        'followed_user_id',
        'pool_type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }
}
