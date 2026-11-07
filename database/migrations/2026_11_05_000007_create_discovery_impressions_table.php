<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_impressions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('viewer_user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('shown_user_id')->constrained('users')->noActionOnDelete();
            $t->string('pool_type', 20);
            $t->timestamp('shown_at')->useCurrent();
            $t->index(['viewer_user_id', 'pool_type', 'shown_at']);
        });

        Schema::create('video_impressions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->timestamp('shown_at')->useCurrent();
            $t->timestamp('completed_at')->nullable();
            $t->index(['user_id', 'shown_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('video_impressions');
        Schema::dropIfExists('user_impressions');
    }
};
