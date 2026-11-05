<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::create('youtube_metrics_monthly',function(Blueprint $t){$t->id();$t->foreignId('youtube_channel_id')->constrained('youtube_channels')->cascadeOnDelete();$t->date('metric_month');$t->unsignedBigInteger('subscriber_count')->nullable();$t->unsignedBigInteger('view_count')->nullable();$t->timestamp('collected_at')->useCurrent();$t->unique(['youtube_channel_id','metric_month']);});}
 public function down(): void {Schema::dropIfExists('youtube_metrics_monthly');}
};