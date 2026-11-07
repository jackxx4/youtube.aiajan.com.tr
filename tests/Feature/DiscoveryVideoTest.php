<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryVideoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private YouTubeChannel $channel;
    /** @var Video[] */
    private array $videos = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'       => 'İzleyici Kullanıcı',
            'email'      => 'izleyici@example.com',
            'username'   => 'izleyicikullanici',
            'google_sub' => 'sub_disc_1',
            'status'     => 'active',
        ]);

        $this->channel = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_TEST_DISC_CHANNEL',
            'title'              => 'Keşif Kanalı',
            'last_synced_at'     => now(),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->videos[] = Video::create([
                'youtube_video_id'   => 'vid_disc_' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'youtube_channel_id' => $this->channel->id,
                'title'              => 'Keşif Videosu ' . $i,
                'availability'       => 'available',
                'published_at'       => now()->subDays($i),
            ]);
        }
    }

    public function test_selection_mode_is_shown_by_default(): void
    {
        $response = $this->actingAs($this->user)->get(route('discovery.videos'));

        $response->assertStatus(200);
        $response->assertSee('Videoları Keşfet');
        $response->assertSee('Seçilen 3 Videoyu Sırayla Oynat');
    }

    public function test_user_can_select_exactly_3_videos(): void
    {
        $selectedIds = [$this->videos[0]->id, $this->videos[1]->id, $this->videos[2]->id];

        $response = $this->actingAs($this->user)->post(route('discovery.videos.select'), [
            'video_ids' => $selectedIds,
        ]);

        $response->assertRedirect(route('discovery.videos'));
        $this->assertEquals($selectedIds, session('selected_video_ids'));

        // Tekrar GET yapıldığında oynatıcı modunda açılmalı
        $playerResponse = $this->actingAs($this->user)->get(route('discovery.videos'));
        $playerResponse->assertStatus(200);
        $playerResponse->assertSee('Seçtiğin 3 Video Sırayla Oynatılıyor');
        $playerResponse->assertSee('Yeni 10 Video Getir / Yeniden Seç');
        $playerResponse->assertSee('Keşif Videosu 1');
    }

    public function test_selection_fails_if_not_exactly_3_videos(): void
    {
        $response = $this->actingAs($this->user)->post(route('discovery.videos.select'), [
            'video_ids' => [$this->videos[0]->id, $this->videos[1]->id], // 2 adet (hatalı)
        ]);

        $response->assertSessionHasErrors('video_ids');
    }

    public function test_user_can_reset_selected_videos(): void
    {
        session(['selected_video_ids' => [$this->videos[0]->id, $this->videos[1]->id, $this->videos[2]->id]]);

        $response = $this->actingAs($this->user)->post(route('discovery.videos.reset'));

        $response->assertRedirect(route('discovery.videos'));
        $this->assertNull(session('selected_video_ids'));
    }
}

