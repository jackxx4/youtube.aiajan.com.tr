<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlatformFollow extends Model {
 public $timestamps=false;
 protected $fillable=['follower_user_id','followed_user_id','pool_type','created_at'];
}