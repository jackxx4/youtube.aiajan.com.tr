<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('youtube_channels',function(Blueprint $t){
   if (!Schema::hasColumn('youtube_channels','subscriber_count')) $t->unsignedBigInteger('subscriber_count')->nullable();
   if (!Schema::hasColumn('youtube_channels','view_count')) $t->unsignedBigInteger('view_count')->nullable();
  });
 }
 public function down(): void {
  Schema::table('youtube_channels',function(Blueprint $t){
   $columns=[]; if (Schema::hasColumn('youtube_channels','subscriber_count')) $columns[]='subscriber_count';
   if (Schema::hasColumn('youtube_channels','view_count')) $columns[]='view_count';
   if ($columns) $t->dropColumn($columns);
  });
 }
};