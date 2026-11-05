<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SupporterStatus extends Model {
    protected $fillable = ['user_id','starts_at','ends_at','status','provider_reference'];
    protected function casts(): array { return ['starts_at'=>'datetime','ends_at'=>'datetime']; }
}