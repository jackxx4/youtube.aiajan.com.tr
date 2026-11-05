<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::table('youtube_channels',function(Blueprint $t){$t->unsignedBigInteger('subscriber_count')->nullable()->after('custom_url');$t->unsignedBigInteger('view_count')->nullable()->after('subscriber_count');});}
 public function down(): void {Schema::table('youtube_channels',function(Blueprint $t){$t->dropColumn(['subscriber_count','view_count']);});}
};