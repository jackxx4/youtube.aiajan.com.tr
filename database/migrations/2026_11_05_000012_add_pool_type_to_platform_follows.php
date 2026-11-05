<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::table('platform_follows',function(Blueprint $t){$t->string('pool_type',20)->default('normal')->after('followed_user_id');});}
 public function down(): void {Schema::table('platform_follows',function(Blueprint $t){$t->dropColumn('pool_type');});}
};