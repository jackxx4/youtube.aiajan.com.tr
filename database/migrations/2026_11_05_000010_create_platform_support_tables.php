<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_blocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('blocker_user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('blocked_user_id')->constrained('users')->noActionOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['blocker_user_id', 'blocked_user_id']);
        });

        Schema::create('contributions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider', 50);
            $t->string('provider_reference')->nullable();
            $t->decimal('amount', 12, 2)->nullable();
            $t->char('currency', 3)->nullable();
            $t->string('status', 20)->default('pending');
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('contributions');
        Schema::dropIfExists('user_blocks');
    }
};
