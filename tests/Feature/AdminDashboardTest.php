<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $response->assertRedirect(route('admin.login'));
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

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get('/yonetim');
        $response->assertStatus(200);
        $response->assertSee('Platform Yönetimi');
        $response->assertSee('Yönetici Kontrol Merkezi');
    }

    public function test_admin_can_login_with_password(): void
    {
        $admin = User::create([
            'name' => 'Yönetici',
            'email' => 'jackalxxxx4@gmail.com',
            'username' => 'adminlogin',
            'role' => 'admin',
            'google_sub' => 'sub_adm_log',
            'password' => Hash::make('test-admin-password-123'),
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email'    => $admin->email,
            'password' => 'test-admin-password-123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('admin_authenticated'));
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
            ->withSession(['admin_authenticated' => true])
            ->post(route('admin.users.toggle_role', $targetUser->id));

        $response->assertSessionHas('status');
        $this->assertEquals('admin', $targetUser->fresh()->role);
    }

    public function test_admin_can_access_users_management_page(): void
    {
        $admin = User::create([
            'name' => 'Yönetici',
            'email' => 'jackalxxxx4@gmail.com',
            'username' => 'adminuser3',
            'role' => 'admin',
            'google_sub' => 'sub_adm_3',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.users'));
        $response->assertStatus(200);
        $response->assertSee('Kullanıcı Yönetimi');
        $response->assertSee('Kayıtlı Üyeler');
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::create([
            'name' => 'Yönetici',
            'email' => 'jackalxxxx4@gmail.com',
            'username' => 'adminuser4',
            'role' => 'admin',
            'google_sub' => 'sub_adm_4',
        ]);

        $targetUser = User::create([
            'name' => 'Silinecek Üye',
            'email' => 'sil@example.com',
            'username' => 'silinecekuser',
            'role' => 'user',
            'google_sub' => 'sub_sil_1',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->delete(route('admin.users.destroy', $targetUser->id));

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::create([
            'name' => 'Yönetici',
            'email' => 'jackalxxxx4@gmail.com',
            'username' => 'adminuser5',
            'role' => 'admin',
            'google_sub' => 'sub_adm_5',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->delete(route('admin.users.destroy', $admin->id));

        $response->assertSessionHasErrors('admin');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}

