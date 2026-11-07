<?php

use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\YouTubeChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // 1. platform_follows tablosuna youtube_channel_id sütununu ekle
        if (Schema::hasTable('platform_follows') && !Schema::hasColumn('platform_follows', 'youtube_channel_id')) {
            Schema::table('platform_follows', function (Blueprint $t) {
                $t->unsignedBigInteger('youtube_channel_id')->nullable()->after('followed_user_id');
            });
        }

        // 2. Eski unique(follower_user_id, followed_user_id) kısıtını kaldır
        // Böylece aynı kullanıcının eklediği farklı kanallar bağımsız olarak takip edilebilir
        try {
            if ($driver === 'sqlsrv') {
                DB::statement("
                    DECLARE @sql NVARCHAR(MAX) = '';
                    SELECT @sql += 'ALTER TABLE platform_follows DROP CONSTRAINT ' + name + ';'
                    FROM sys.key_constraints
                    WHERE parent_object_id = OBJECT_ID('platform_follows') AND type = 'UQ';
                    IF @sql <> '' EXEC sp_executesql @sql;
                ");
            } else {
                Schema::table('platform_follows', function (Blueprint $t) {
                    try {
                        $t->dropUnique(['follower_user_id', 'followed_user_id']);
                    } catch (\Throwable) {}
                });
            }
        } catch (\Throwable $e) {
            // Kısıt zaten yoksa yoksay
        }

        // 3. Yeni filtrelenmiş benzersiz dizini oluştur (follower_user_id, youtube_channel_id)
        try {
            if (in_array($driver, ['sqlite', 'sqlsrv'], true)) {
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS platform_follows_follower_channel_unique ON platform_follows (follower_user_id, youtube_channel_id) WHERE youtube_channel_id IS NOT NULL");
            }
        } catch (\Throwable $e) {
            // Dizin zaten varsa yoksay
        }

        // 4. google_channel_ids'e sahip kullanıcıların kanallarını user_channel_links tablosuna kaydet
        try {
            foreach (User::whereNotNull('google_channel_ids')->cursor() as $u) {
                $ids = (array) $u->google_channel_ids;
                if (!empty($ids)) {
                    $channels = YouTubeChannel::whereIn('youtube_channel_id', $ids)->get();
                    foreach ($channels as $ch) {
                        UserChannelLink::firstOrCreate(
                            ['user_id' => $u->id, 'youtube_channel_id' => $ch->id],
                            ['started_at' => now(), 'ended_at' => now()]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            // Veri senkronizasyonu hata verirse yoksay
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_follows') && Schema::hasColumn('platform_follows', 'youtube_channel_id')) {
            Schema::table('platform_follows', function (Blueprint $t) {
                $t->dropColumn('youtube_channel_id');
            });
        }
    }
};
