<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (DB::connection()->getDriverName() === 'sqlite') {
   DB::statement('CREATE UNIQUE INDEX user_channel_links_active_user_unique ON user_channel_links (user_id) WHERE ended_at IS NULL');
   DB::statement('CREATE UNIQUE INDEX user_channel_links_active_channel_unique ON user_channel_links (youtube_channel_id) WHERE ended_at IS NULL');
   return;
  }

  Schema::table('user_channel_links',function(Blueprint $t){
   $t->unsignedBigInteger('active_user_id')->nullable()->storedAs('IF(ended_at IS NULL, user_id, NULL)');
   $t->unsignedBigInteger('active_channel_id')->nullable()->storedAs('IF(ended_at IS NULL, youtube_channel_id, NULL)');
   $t->unique('active_user_id');
   $t->unique('active_channel_id');
  });
 }
 public function down(): void {
  if (DB::connection()->getDriverName() === 'sqlite') {
   DB::statement('DROP INDEX user_channel_links_active_user_unique');
   DB::statement('DROP INDEX user_channel_links_active_channel_unique');
   return;
  }

  Schema::table('user_channel_links',function(Blueprint $t){
   $t->dropUnique(['active_user_id']);
   $t->dropUnique(['active_channel_id']);
   $t->dropColumn(['active_user_id','active_channel_id']);
  });
 }
};
