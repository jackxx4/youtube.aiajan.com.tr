<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MS SQL Server'da aynı tabloda users'a birden fazla CASCADE FK tanımlanamaz
 * ("multiple cascade paths" hatası). Bu migration etkilenen FK'ları
 * NO ACTION olarak yeniden yapılandırır.
 */
return new class extends Migration {
    public function up(): void {
        $isSqlSrv = DB::getDriverName() === 'sqlsrv';

        $this->fixTable('platform_follows', ['follower_user_id', 'followed_user_id'], $isSqlSrv);
        $this->fixTable('user_blocks',      ['blocker_user_id', 'blocked_user_id'],   $isSqlSrv);
        $this->fixTable('user_impressions', ['viewer_user_id',  'shown_user_id'],     $isSqlSrv);
    }

    private function fixTable(string $table, array $columns, bool $isSqlSrv): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($columns, $isSqlSrv) {
            foreach ($columns as $col) {
                if ($isSqlSrv) {
                    try { $t->dropForeign([$col]); } catch (\Throwable) {}
                }
                try {
                    $t->foreign($col)->references('id')->on('users')->onDelete('no action');
                } catch (\Throwable) {}
            }
        });
    }

    public function down(): void {}
};
