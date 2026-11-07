<?php

use App\Models\Video;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // 1. user_video_selections tablosuna youtube_channel_id sütununu ekle
        if (Schema::hasTable('user_video_selections') && !Schema::hasColumn('user_video_selections', 'youtube_channel_id')) {
            Schema::table('user_video_selections', function (Blueprint $t) {
                $t->unsignedBigInteger('youtube_channel_id')->nullable()->after('video_id');
            });
        }

        // 2. Mevcut kayıtların youtube_channel_id değerlerini videos tablosundan doldur
        try {
            DB::statement("
                UPDATE user_video_selections
                SET youtube_channel_id = (
                    SELECT youtube_channel_id FROM videos WHERE videos.id = user_video_selections.video_id
                )
                WHERE youtube_channel_id IS NULL
            ");
        } catch (\Throwable $e) {
            // Veritabanı dialect farkı varsa tek tek güncelle
            try {
                $selections = DB::table('user_video_selections')->whereNull('youtube_channel_id')->get();
                foreach ($selections as $sel) {
                    $video = DB::table('videos')->where('id', $sel->video_id)->first();
                    if ($video && $video->youtube_channel_id) {
                        DB::table('user_video_selections')->where('id', $sel->id)->update([
                            'youtube_channel_id' => $video->youtube_channel_id,
                        ]);
                    }
                }
            } catch (\Throwable) {}
        }

        // 3. İndeks ekle
        try {
            Schema::table('user_video_selections', function (Blueprint $t) {
                $t->index(['user_id', 'youtube_channel_id'], 'user_channel_selections_index');
            });
        } catch (\Throwable) {}
    }

    public function down(): void
    {
        if (Schema::hasTable('user_video_selections') && Schema::hasColumn('user_video_selections', 'youtube_channel_id')) {
            Schema::table('user_video_selections', function (Blueprint $t) {
                $t->dropColumn('youtube_channel_id');
            });
        }
    }
};

