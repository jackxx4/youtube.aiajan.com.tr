<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::create('youtube_oauth_tokens',function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();$t->longText('access_token_enc');$t->longText('refresh_token_enc')->nullable();$t->timestamp('expires_at')->nullable();$t->text('scopes')->nullable();$t->timestamps();});}
 public function down(): void {Schema::dropIfExists('youtube_oauth_tokens');}
};