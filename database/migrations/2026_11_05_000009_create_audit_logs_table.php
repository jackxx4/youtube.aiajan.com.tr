<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::create('audit_logs',function(Blueprint $t){$t->id();$t->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();$t->string('action',100);$t->string('entity_type',100)->nullable();$t->string('entity_id',100)->nullable();$t->json('metadata')->nullable();$t->char('ip_hash',64)->nullable();$t->timestamp('created_at')->useCurrent();$t->index(['actor_user_id','created_at']);});}
 public function down(): void {Schema::dropIfExists('audit_logs');}
};