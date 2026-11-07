<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoShowcaseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private YouTubeChannel $channel;
    private Video $video1;
    private Video $video2;
    private Video $video3;
    private Video $video4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Vitrin Kullanıcısı',
            'email' => 'vitrin@example.com',
            'username' => 'vitrinuser',
            'google_sub' => 'sub_vitrin_1',
        ]);

        $this->channel = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_SHOWCASE_1234567890',
            'title' => 'Vitrin Kanalı',
            'last_synced_at' => now(),
        ]);

        UserChannelLink::create([
            'user_id' => $this->user->id,
            'youtube_channel_id' => $this->channel->id,
            'started_at' => now(),
        ]);

        $this->video1 = Video::create([
            'youtube_video_id' => 'vid00000001',
            'youtube_channel_id' => $this->channel->id,
            'title' => 'Video 1',
            'availability' => 'available',
            'published_at' => now()->subDay(),
        ]);

        $this->video2 = Video::create([
            'youtube_video_id' => 'vid00000002',
            'youtube_channel_id' => $this->channel->id,
            'title' => 'Video 2',
            'availability' => 'available',
            'published_at' => now()->subHours(10),
        ]);

        $this->video3 = Video::create([
            'youtube_video_id' => 'vid00000003',
            'youtube_channel_id' => $this->channel->id,
            'title' => 'Video 3',
            'availability' => 'available',
            'published_at' => now()->subHours(5),
        ]);

        $this->video4 = Video::create([
            'youtube_video_id' => 'vid00000004',
            'youtube_channel_id' => $this->channel->id,
            'title' => 'Video 4',
            'availability' => 'available',
            'published_at' => now()->subHour(),
        ]);
    }

    public function test_user_can_remove_single_video_from_showcase(): void
    {
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video1->id]);
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video2->id]);

        $this->assertEquals(2, UserVideoSelection::where('user_id', $this->user->id)->count());

        $response = $this->actingAs($this->user)
            ->delete(route('videos.showcase.remove', $this->video1->id));

        $response->assertSessionHas('status');
        $this->assertEquals(1, UserVideoSelection::where('user_id', $this->user->id)->count());
        $this->assertFalse(UserVideoSelection::where('user_id', $this->user->id)->where('video_id', $this->video1->id)->exists());
    }

    public function test_user_can_add_single_video_to_showcase(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('videos.showcase.add_single', $this->video1->id));

        $response->assertSessionHas('status');
        $this->assertTrue(UserVideoSelection::where('user_id', $this->user->id)->where('video_id', $this->video1->id)->exists());
    }

    public function test_user_cannot_exceed_3_videos_via_add_single(): void
    {
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video1->id]);
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video2->id]);
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video3->id]);

        $response = $this->actingAs($this->user)
            ->post(route('videos.showcase.add_single', $this->video4->id));

        $response->assertSessionHasErrors('error');
        $this->assertEquals(3, UserVideoSelection::where('user_id', $this->user->id)->count());
    }

    public function test_user_can_clear_all_showcase_videos(): void
    {
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video1->id]);
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video2->id]);

        $response = $this->actingAs($this->user)
            ->post(route('videos.showcase.clear'));

        $response->assertSessionHas('status');
        $this->assertEquals(0, UserVideoSelection::where('user_id', $this->user->id)->count());
    }

    public function test_user_can_empty_showcase_via_form_update(): void
    {
        UserVideoSelection::create(['user_id' => $this->user->id, 'video_id' => $this->video1->id]);

        $response = $this->actingAs($this->user)
            ->post(route('videos.showcase.update'), [
                'video_ids' => [],
            ]);

        $response->assertRedirect(route('videos.showcase'));
        $this->assertEquals(0, UserVideoSelection::where('user_id', $this->user->id)->count());
    }

    public function test_user_can_view_showcase_page_with_latest_videos(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('videos.showcase'));

        $response->assertStatus(200);
        $response->assertSee('Video Vitrinim');
        $response->assertSee('Kanalınızın Son Videoları');
        $response->assertSee('Video 1');
        $response->assertSee('Video 2');
        $response->assertSee('Video 3');
        $response->assertSee('Video 4');
        $response->assertSee('💾 Vitrini Kaydet');
    }

    public function test_user_can_select_3_videos_via_form_update(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('videos.showcase.update'), [
                'video_ids' => [$this->video1->id, $this->video2->id, $this->video3->id],
            ]);

        $response->assertRedirect(route('videos.showcase'));
        $response->assertSessionHas('status');

        $selected = UserVideoSelection::where('user_id', $this->user->id)->pluck('video_id')->all();
        $this->assertCount(3, $selected);
        $this->assertContains($this->video1->id, $selected);
        $this->assertContains($this->video2->id, $selected);
        $this->assertContains($this->video3->id, $selected);
    }
}

