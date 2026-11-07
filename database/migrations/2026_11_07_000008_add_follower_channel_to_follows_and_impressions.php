<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('platform_follows', 'follower_channel_id')) {
            Schema::table('platform_follows', function (Blueprint $table) {
                $table->unsignedBigInteger('follower_channel_id')->nullable()->after('follower_user_id');
                $table->index(['follower_user_id', 'follower_channel_id'], 'pf_follower_user_channel_idx');
            });

            // Mevcut takip kayıtlarını kullanıcının aktif kanalına bağla
            $activeLinks = DB::table('user_channel_links')
                ->whereNull('ended_at')
                ->select('user_id', 'youtube_channel_id')
                ->get();

            foreach ($activeLinks as $link) {
                DB::table('platform_follows')
                    ->where('follower_user_id', $link->user_id)
                    ->whereNull('follower_channel_id')
                    ->update(['follower_channel_id' => $link->youtube_channel_id]);
            }

            // Eski tekil (follower_user_id, youtube_channel_id) benzersiz dizinini kaldır ki
            // aynı kullanıcının farklı kanalları (follower_channel_id) bağımsız takip görevi yapabilsin
            try {
                $driver = DB::connection()->getDriverName();
                if ($driver === 'sqlsrv') {
                    DB::statement("IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'platform_follows_follower_channel_unique' AND object_id = OBJECT_ID('platform_follows')) DROP INDEX platform_follows_follower_channel_unique ON platform_follows");
                } elseif ($driver === 'sqlite') {
                    DB::statement("DROP INDEX IF EXISTS platform_follows_follower_channel_unique");
                }
            } catch (\Throwable) {}
        }

        if (!Schema::hasColumn('user_impressions', 'viewer_channel_id')) {
            Schema::table('user_impressions', function (Blueprint $table) {
                $table->unsignedBigInteger('viewer_channel_id')->nullable()->after('viewer_user_id');
                $table->index(['viewer_user_id', 'viewer_channel_id'], 'ui_viewer_user_channel_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_impressions', 'viewer_channel_id')) {
            Schema::table('user_impressions', function (Blueprint $table) {
                $table->dropIndex('ui_viewer_user_channel_idx');
                $table->dropColumn('viewer_channel_id');
            });
        }

        if (Schema::hasColumn('platform_follows', 'follower_channel_id')) {
            Schema::table('platform_follows', function (Blueprint $table) {
                $table->dropIndex('pf_follower_user_channel_idx');
                $table->dropColumn('follower_channel_id');
            });
        }
    }
};
