<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\YouTubeChannel;
use App\Models\UserChannelLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_channel_selection(): void
    {
        $response = $this->get('/kanal-secimi');
        $response->assertRedirect('/giris');
    }

    public function test_authenticated_user_can_view_channel_selection_page(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'google_sub' => 'google_sub_123',
        ]);

        $response = $this->actingAs($user)->get('/kanal-secimi');
        $response->assertStatus(200);
        $response->assertSee('Temsil edeceğin YouTube kanalını bağla');
    }

    public function test_user_can_switch_channel_cleanly(): void
    {
        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'username' => 'testuser2',
            'google_sub' => 'google_sub_456',
        ]);

        $channelA = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_TEST_A_123456789012',
            'title' => 'Kanal A',
            'last_synced_at' => now(),
        ]);

        $channelB = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_TEST_B_123456789012',
            'title' => 'Kanal B',
            'last_synced_at' => now(),
        ]);

        $linkA = UserChannelLink::create([
            'user_id' => $user->id,
            'youtube_channel_id' => $channelA->id,
            'started_at' => now()->subDay(),
        ]);

        // Listeden Kanal B'yi seçerek değiştirme
        $response = $this->actingAs($user)
            ->withSession(['available_youtube_channels' => [$channelB->youtube_channel_id]])
            ->post('/kanal-secimi', [
                'channel_id' => $channelB->youtube_channel_id,
            ]);

        $response->assertRedirect(route('videos.showcase'));

        // Kanal A'nın ended_at değeri dolmuş olmalı
        $this->assertNotNull($linkA->fresh()->ended_at);

        // Kullanıcının aktif kanalı Kanal B olmalı
        $activeLink = $user->fresh()->activeChannelLink;
        $this->assertNotNull($activeLink);
        $this->assertEquals($channelB->id, $activeLink->youtube_channel_id);
    }
}
