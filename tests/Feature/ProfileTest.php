<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\YouTubeChannel;
use App\Models\UserChannelLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_profile(): void
    {
        $response = $this->get('/profil');
        $response->assertRedirect('/giris');
    }

    public function test_authenticated_user_is_redirected_from_profile_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Deneme Kullanıcı',
            'email' => 'deneme@example.com',
            'username' => 'denemekullanici',
            'google_sub' => 'sub_9999',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/profil');
        $response->assertRedirect(route('dashboard'));

        $dashResponse = $this->actingAs($user)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Deneme Kullanıcı');
        $dashResponse->assertSee('@denemekullanici');
    }

    public function test_user_can_link_channel_from_profile_without_403(): void
    {
        $user = User::create([
            'name' => 'Deneme Kullanıcı 2',
            'email' => 'deneme2@example.com',
            'username' => 'deneme2',
            'google_sub' => 'sub_8888',
            'status' => 'active',
        ]);

        $channel = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_TEST_PROFILE_CHANNEL',
            'title' => 'Profil Kanalı',
            'last_synced_at' => now(),
        ]);

        // Profil sayfasından veya listeden kanal bağlama (POST /kanal-secimi)
        $response = $this->actingAs($user)->post('/kanal-secimi', [
            'channel_id' => $channel->youtube_channel_id,
        ]);

        $response->assertRedirect(route('videos.showcase'));
        $this->assertDatabaseHas('user_channel_links', [
            'user_id' => $user->id,
            'youtube_channel_id' => $channel->id,
        ]);
    }

    public function test_public_user_profile_is_accessible(): void
    {
        $viewer = User::create([
            'name' => 'İzleyici',
            'email' => 'izleyici@example.com',
            'username' => 'izleyici',
            'google_sub' => 'sub_view_1',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Halka Açık Üretici',
            'email' => 'halka@example.com',
            'username' => 'halkauye',
            'google_sub' => 'sub_7777',
            'status' => 'active',
        ]);

        $response = $this->actingAs($viewer)->get('/kullanici/halkauye');
        $response->assertStatus(200);
        $response->assertSee('Halka Açık Üretici');
    }
}

