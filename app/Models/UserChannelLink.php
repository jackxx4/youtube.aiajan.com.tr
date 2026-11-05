<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class UserChannelLink extends Model {
    public $timestamps = false;
    protected $fillable = ['user_id','youtube_channel_id','started_at','ended_at'];
    protected function casts(): array { return ['started_at'=>'datetime','ended_at'=>'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function channel(): BelongsTo { return $this->belongsTo(YouTubeChannel::class,'youtube_channel_id'); }
}