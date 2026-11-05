<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('platform_follows',function(Blueprint $t){
  $t->id(); $t->foreignId('follower_user_id')->constrained('users')->cascadeOnDelete(); $t->foreignId('followed_user_id')->constrained('users')->cascadeOnDelete();
  $t->timestamp('created_at')->useCurrent(); $t->unique(['follower_user_id','followed_user_id']); $t->index(['followed_user_id','created_at']);
 }); }
 public function down(): void { Schema::dropIfExists('platform_follows'); }
};