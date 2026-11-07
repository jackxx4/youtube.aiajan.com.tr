<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::create([
            'name' => 'Normal Üye',
            'email' => 'normal@example.com',
            'username' => 'normaluser',
            'role' => 'user',
            'google_sub' => 'sub_reg_1',
        ]);

        $response = $this->actingAs($user)->get('/yonetim');
        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Yönetici',
            'email' => 'jackalxxxx4@gmail.com',
            'username' => 'adminuser',
            'role' => 'admin',
            'google_sub' => 'sub_adm_1',
        ]);

        $response = $this->actingAs($admin)->get('/yonetim');
        $response->assertStatus(200);
        $response->assertSee('Platform Yönetimi');
        $response->assertSee('Yönetici Kontrol Merkezi');
    }

    public function test_admin_can_toggle_user_role(): void
    {
        $admin = User::create([
            'name' => 'Yönetici',
            'email' => 'jackalxxxx4@gmail.com',
            'username' => 'adminuser2',
            'role' => 'admin',
            'google_sub' => 'sub_adm_2',
        ]);

        $targetUser = User::create([
            'name' => 'Hedef Üye',
            'email' => 'hedef@example.com',
            'username' => 'targetuser',
            'role' => 'user',
            'google_sub' => 'sub_target_1',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.toggle_role', $targetUser->id));

        $response->assertSessionHas('status');
        $this->assertEquals('admin', $targetUser->fresh()->role);
    }
}

