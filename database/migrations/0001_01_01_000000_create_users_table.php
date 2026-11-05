<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('users',function(Blueprint $table){
   $table->id(); $table->string('google_sub',191)->unique(); $table->string('name'); $table->string('email')->unique();
   $table->string('username',50)->unique(); $table->text('avatar_url')->nullable(); $table->string('role',20)->default('user');
   $table->string('status',20)->default('active'); $table->timestamp('email_verified_at')->nullable();
   $table->string('password')->nullable(); $table->rememberToken(); $table->timestamps();
  });
 };
 public function down(): void { Schema::dropIfExists('users'); }
};