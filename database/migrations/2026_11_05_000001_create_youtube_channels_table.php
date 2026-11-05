<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('youtube_channels',function(Blueprint $t){
  $t->id(); $t->string('youtube_channel_id',64)->unique(); $t->string('title'); $t->text('thumbnail_url')->nullable();
  $t->string('custom_url')->nullable(); $t->unsignedBigInteger('subscriber_count')->nullable(); $t->unsignedBigInteger('view_count')->nullable();
  $t->timestamp('last_synced_at')->nullable(); $t->timestamps();
 }); }
 public function down(): void { Schema::dropIfExists('youtube_channels'); }
};