<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouTubeMetricMonthly extends Model
{
    protected $table = 'youtube_metrics_monthly';
    protected $fillable = ['youtube_channel_id', 'metric_month', 'views_monthly', 'subscribers_net_monthly', 'calculated_score', 'synced_at'];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(YouTubeChannel::class, 'youtube_channel_id');
    }
}
