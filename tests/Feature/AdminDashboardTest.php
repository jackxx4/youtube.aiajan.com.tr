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

    public function test_admin_cannot_toggle_user_role_from_users_table(): void
    {
        $admin = \App\Models\Admin::firstOrCreate(
            ['username' => 'emrah'],
            [
                'name'      => 'Emrah',
                'email'     => 'emrah@aiajan.com.tr',
                'password'  => Hash::make('985758'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $targetUser = User::create([
            'name'       => 'Hedef Üye',
            'email'      => 'hedef@example.com',
            'username'   => 'targetuser',
            'role'       => 'user',
            'google_sub' => 'sub_target_1',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.users.toggle_role', $targetUser->id));

        $response->assertSessionHasErrors('admin');
        $this->assertEquals('user', $targetUser->fresh()->role);
    }

    public function test_admin_can_manage_isolated_admins_crud(): void
    {
        $admin = \App\Models\Admin::firstOrCreate(
            ['username' => 'emrah'],
            [
                'name'      => 'Emrah',
                'email'     => 'emrah@aiajan.com.tr',
                'password'  => Hash::make('985758'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        // 1. Yöneticiler sayfasını görüntüleme
        $response = $this->actingAs($admin, 'admin')->get(route('admin.admins'));
        $response->assertStatus(200);
        $response->assertSee('Yönetici Yönetimi');

        // 2. Yeni yönetici ekleme
        $storeResponse = $this->actingAs($admin, 'admin')->post(route('admin.admins.store'), [
            'name'     => 'Yeni Admin',
            'username' => 'yeniadmin',
            'email'    => 'yeniadmin@example.com',
            'password' => 'secret123',
            'role'     => 'admin',
        ]);

        $storeResponse->assertSessionHas('status');
        $this->assertDatabaseHas('admins', ['username' => 'yeniadmin']);

        $newAdmin = \App\Models\Admin::where('username', 'yeniadmin')->first();

        // 3. Durumunu değiştirme
        $toggleResponse = $this->actingAs($admin, 'admin')->post(route('admin.admins.toggle_status', $newAdmin->id));
        $toggleResponse->assertSessionHas('status');
        $this->assertFalse((bool) $newAdmin->fresh()->is_active);

        // 4. Yöneticiyi silme
        $deleteResponse = $this->actingAs($admin, 'admin')->delete(route('admin.admins.destroy', $newAdmin->id));
        $deleteResponse->assertSessionHas('status');
        $this->assertDatabaseMissing('admins', ['id' => $newAdmin->id]);
    }

    public function test_admin_can_view_and_destroy_showcase_video(): void
    {
        $admin = \App\Models\Admin::firstOrCreate(
            ['username' => 'emrah'],
            [
                'name'      => 'Emrah',
                'email'     => 'emrah@aiajan.com.tr',
                'password'  => Hash::make('985758'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $user = User::create([
            'name'       => 'Vitrin Sahibi',
            'email'      => 'vitrin@example.com',
            'username'   => 'vitrinsahibi',
            'role'       => 'user',
            'google_sub' => 'sub_vitrin_1',
        ]);

        $channel = \App\Models\YouTubeChannel::create([
            'youtube_channel_id' => 'UC_SHOWCASE_CH',
            'title'              => 'Vitrin Kanalı',
            'is_active'          => true,
        ]);

        $video = \App\Models\Video::create([
            'youtube_video_id'   => 'v_showcase_999',
            'youtube_channel_id' => $channel->id,
            'title'              => 'Harika Vitrin Videosu',
            'availability'       => 'available',
        ]);

        $selection = \App\Models\UserVideoSelection::create([
            'user_id'  => $user->id,
            'video_id' => $video->id,
        ]);

        // Sayfayı görüntüleme
        $response = $this->actingAs($admin, 'admin')->get(route('admin.showcase_videos'));
        $response->assertStatus(200);
        $response->assertSee('Vitrin Videoları');
        $response->assertSee('Harika Vitrin Videosu');

        // Vitrinden kaldırma
        $destroyResponse = $this->actingAs($admin, 'admin')->delete(route('admin.showcase_videos.destroy', $selection->id));
        $destroyResponse->assertSessionHas('status');
        $this->assertDatabaseMissing('user_video_selections', ['id' => $selection->id]);
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

    public function test_isolated_admin_can_login_with_emrah_and_password(): void
    {
        $response = $this->post(route('admin.login.submit'), [
            'login'    => 'emrah',
            'password' => '985758',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(auth('admin')->check());
        $this->assertEquals('emrah', auth('admin')->user()->username);
    }

    public function test_admin_can_delete_channel_and_its_associated_data(): void
    {
        $admin = \App\Models\Admin::firstOrCreate(
            ['username' => 'emrah'],
            [
                'name'      => 'Emrah',
                'email'     => 'emrah@aiajan.com.tr',
                'password'  => Hash::make('985758'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $user = User::create([
            'name'       => 'Test Üretici',
            'email'      => 'uretici@example.com',
            'username'   => 'uretici1',
            'role'       => 'user',
            'google_sub' => 'sub_uretici_1',
        ]);

        $channel = \App\Models\YouTubeChannel::create([
            'youtube_channel_id' => 'UC_TEST_CHANNEL_DEL',
            'title'              => 'Silinecek YouTube Kanalı',
            'subscriber_count'   => 500,
            'is_active'          => true,
        ]);

        \Illuminate\Support\Facades\DB::table('user_channel_links')->insert([
            'user_id'            => $user->id,
            'youtube_channel_id' => $channel->id,
            'started_at'         => now(),
        ]);

        $video = \App\Models\Video::create([
            'youtube_video_id'   => 'v_del_12345',
            'youtube_channel_id' => $channel->id,
            'title'              => 'Silinecek Video',
            'availability'       => 'available',
        ]);

        \App\Models\UserVideoSelection::create([
            'user_id'  => $user->id,
            'video_id' => $video->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.channels.destroy', $channel->id));

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('youtube_channels', ['id' => $channel->id]);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
        $this->assertDatabaseMissing('user_video_selections', ['video_id' => $video->id]);
        $this->assertDatabaseMissing('user_channel_links', ['youtube_channel_id' => $channel->id]);
    }

    public function test_admin_can_access_channels_page(): void
    {
        $admin = \App\Models\Admin::firstOrCreate(
            ['username' => 'emrah'],
            [
                'name'      => 'Emrah',
                'email'     => 'emrah@aiajan.com.tr',
                'password'  => Hash::make('985758'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($admin, 'admin')->get(route('admin.channels'));
        $response->assertStatus(200);
        $response->assertSee('Kanal Yönetimi');
    }

    public function test_admin_can_clean_orphan_channels(): void
    {
        $admin = \App\Models\Admin::firstOrCreate(
            ['username' => 'emrah'],
            [
                'name'      => 'Emrah',
                'email'     => 'emrah@aiajan.com.tr',
                'password'  => Hash::make('985758'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $orphan = \App\Models\YouTubeChannel::create([
            'youtube_channel_id' => 'UC_ORPHAN_CH',
            'title'              => 'Sahipsiz Kanal',
            'is_active'          => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.channels.clean_orphans'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('youtube_channels', ['id' => $orphan->id]);
    }
}

