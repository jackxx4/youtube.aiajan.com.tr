<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::create('videos',function(Blueprint $t){$t->id();$t->string('youtube_video_id',32)->unique();$t->foreignId('youtube_channel_id')->constrained('youtube_channels')->cascadeOnDelete();$t->string('title',500);$t->text('thumbnail_url')->nullable();$t->timestamp('published_at')->nullable();$t->string('availability',20)->default('available');$t->timestamp('last_checked_at')->nullable();$t->timestamps();$t->index(['availability','published_at']);});}
 public function down(): void {Schema::dropIfExists('videos');}
};