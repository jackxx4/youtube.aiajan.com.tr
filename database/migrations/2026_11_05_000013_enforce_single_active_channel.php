<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('user_channel_links',function(Blueprint $t){
   $t->unsignedBigInteger('active_user_id')->nullable()->storedAs('IF(ended_at IS NULL, user_id, NULL)');
   $t->unsignedBigInteger('active_channel_id')->nullable()->storedAs('IF(ended_at IS NULL, youtube_channel_id, NULL)');
   $t->unique('active_user_id');
   $t->unique('active_channel_id');
  });
 }
 public function down(): void {
  Schema::table('user_channel_links',function(Blueprint $t){
   $t->dropUnique(['active_user_id']);
   $t->dropUnique(['active_channel_id']);
   $t->dropColumn(['active_user_id','active_channel_id']);
  });
 }
};