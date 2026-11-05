<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('user_channel_links',function(Blueprint $t){
   $t->id();
   $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
   $t->foreignId('youtube_channel_id')->constrained('youtube_channels')->cascadeOnDelete();
   $t->timestamp('started_at');
   $t->timestamp('ended_at')->nullable();
   $t->index(['user_id','ended_at']);
   $t->index(['youtube_channel_id','ended_at']);
  });
 }
 public function down(): void {Schema::dropIfExists('user_channel_links');}
};