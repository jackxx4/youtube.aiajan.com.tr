<?php

namespace Tests\Feature;

use App\Models\PlatformFollow;
use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\YouTubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_exactly_three_eligible_creators(): void
    {
        $viewer = User::factory()->create();
        $creators = $this->creators(3);

        $this->actingAs($viewer)
            ->post(route('discovery.follow', 'normal'), ['user_ids' => $creators->modelKeys()])
            ->assertRedirect(route('discovery.users', 'normal'));

        $this->assertSame(3, PlatformFollow::where('follower_user_id', $viewer->id)->count());
        $this->assertDatabaseHas('platform_follows', [
            'follower_user_id' => $viewer->id,
            'followed_user_id' => $creators->first()->id,
            'pool_type' => 'normal',
        ]);
    }

    public function test_user_cannot_exceed_the_daily_limit_for_a_pool(): void
    {
        $viewer = User::factory()->create();
        $followed = $this->creators(3);
        $candidates = $this->creators(3);

        foreach ($followed as $creator) {
            PlatformFollow::create([
                'follower_user_id' => $viewer->id,
                'followed_user_id' => $creator->id,
                'pool_type' => 'normal',
            ]);
        }

        $this->actingAs($viewer)
            ->post(route('discovery.follow', 'normal'), ['user_ids' => $candidates->modelKeys()])
            ->assertStatus(429);
    }

    private function creators(int $count)
    {
        return User::factory()->count($count)->create()->each(function (User $creator): void {
            $channel = YouTubeChannel::create([
                'youtube_channel_id' => 'channel-'.$creator->id,
                'title' => 'Kanal '.$creator->id,
            ]);

            UserChannelLink::create([
                'user_id' => $creator->id,
                'youtube_channel_id' => $channel->id,
                'started_at' => now(),
            ]);
        });
    }
}
