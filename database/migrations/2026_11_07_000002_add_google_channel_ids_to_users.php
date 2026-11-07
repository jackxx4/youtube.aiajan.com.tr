<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->json('google_channel_ids')->nullable()->after('avatar_url');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('google_channel_ids');
        });
    }
};
