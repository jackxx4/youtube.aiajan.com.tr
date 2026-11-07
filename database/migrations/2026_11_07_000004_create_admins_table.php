<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->default('super_admin');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // İlk yönetici hesabını oluştur (eğer henüz yoksa)
        if (Schema::hasTable('admins') && !DB::table('admins')->where('email', 'jackalxxxx4@gmail.com')->exists()) {
            DB::table('admins')->insert([
                'name'       => 'Platform Yöneticisi',
                'email'      => 'jackalxxxx4@gmail.com',
                'password'   => Hash::make('AiajanAdmin2026!'),
                'role'       => 'super_admin',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};

