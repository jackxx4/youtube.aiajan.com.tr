<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Video extends Model {
    protected $fillable = ['youtube_video_id','youtube_channel_id','title','thumbnail_url','published_at','availability','last_checked_at'];
    protected function casts(): array { return ['published_at'=>'datetime','last_checked_at'=>'datetime']; }
    public function channel(): BelongsTo { return $this->belongsTo(YouTubeChannel::class,'youtube_channel_id'); }
}