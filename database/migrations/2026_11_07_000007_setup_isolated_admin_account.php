<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. admins tablosuna username sütunu ekle
        if (Schema::hasTable('admins') && !Schema::hasColumn('admins', 'username')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('username', 64)->nullable()->after('name');
            });
        }

        // 2. Eski jackalxxxx4@gmail.com admin kaydını admins tablosundan sil
        // Böylece yönetim hesabı kişisel YouTube/Google hesabından tamamen ayrılır
        if (Schema::hasTable('admins')) {
            DB::table('admins')->where('email', 'jackalxxxx4@gmail.com')->delete();
        }

        // 3. Emrah yönetici hesabını oluştur veya güncelle
        if (Schema::hasTable('admins')) {
            $existing = DB::table('admins')
                ->where('username', 'emrah')
                ->orWhere('email', 'emrah@aiajan.com.tr')
                ->first();

            if ($existing) {
                DB::table('admins')->where('id', $existing->id)->update([
                    'name'       => 'Emrah',
                    'username'   => 'emrah',
                    'email'      => 'emrah@aiajan.com.tr',
                    'password'   => Hash::make('985758'),
                    'role'       => 'super_admin',
                    'is_active'  => true,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('admins')->insert([
                    'name'       => 'Emrah',
                    'username'   => 'emrah',
                    'email'      => 'emrah@aiajan.com.tr',
                    'password'   => Hash::make('985758'),
                    'role'       => 'super_admin',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. users tablosundaki kullanıcı hesabının rolünü creator olarak güncelle
        if (Schema::hasTable('users')) {
            DB::table('users')->where('email', 'jackalxxxx4@gmail.com')->update([
                'role' => 'user',
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'username')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};

