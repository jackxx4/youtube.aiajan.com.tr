<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class YouTubeMetricMonthly extends Model {
 public $timestamps=false;
 protected $table='youtube_metrics_monthly';
 protected $fillable=['youtube_channel_id','metric_month','subscriber_count','view_count','collected_at'];
 protected function casts(): array {return ['metric_month'=>'date','collected_at'=>'datetime'];}
}