<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('supporter_statuses',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->timestamp('starts_at');$t->timestamp('ends_at');$t->string('status',20)->default('active');$t->string('provider_reference')->nullable();$t->timestamps();$t->index(['status','ends_at']);});}
 public function down(): void {Schema::dropIfExists('supporter_statuses');}
};