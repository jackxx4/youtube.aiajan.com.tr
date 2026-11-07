<?php

namespace Tests\Feature;

use App\Models\PlatformFollow;
use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\YouTubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryChannelTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $otherUser;
    private YouTubeChannel $myChannel;
    /** @var YouTubeChannel[] */
    private array $otherChannels = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::create([
            'name'       => 'Benim Hesabım',
            'email'      => 'benim@example.com',
            'username'   => 'benimhesabim',
            'google_sub' => 'sub_me_1',
            'status'     => 'active',
        ]);

        $this->myChannel = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_MY_CHANNEL',
            'title'              => 'Benim Kanalım',
            'last_synced_at'     => now(),
        ]);

        UserChannelLink::create([
            'user_id'            => $this->me->id,
            'youtube_channel_id' => $this->myChannel->id,
            'started_at'         => now(),
        ]);

        $this->otherUser = User::create([
            'name'       => 'Diğer Üretici',
            'email'      => 'diger@example.com',
            'username'   => 'digeruretici',
            'google_sub' => 'sub_other_1',
            'status'     => 'active',
        ]);

        // Diğer kullanıcının 3 ayrı kanalı var
        for ($i = 1; $i <= 3; $i++) {
            $ch = YouTubeChannel::create([
                'youtube_channel_id' => 'UC_OTHER_CHANNEL_' . $i,
                'title'              => 'Diğer Kanal ' . $i,
                'last_synced_at'     => now(),
            ]);

            UserChannelLink::create([
                'user_id'            => $this->otherUser->id,
                'youtube_channel_id' => $ch->id,
                'started_at'         => now(),
                'ended_at'           => $i === 1 ? null : now(), // 1. kanal aktif, diğerleri ekli alternatif kanallar
            ]);

            $this->otherChannels[] = $ch;
        }
    }

    public function test_discovery_page_shows_all_channels_added_by_other_users(): void
    {
        $response = $this->actingAs($this->me)->get(route('discovery.users', 'normal'));

        $response->assertStatus(200);
        // Kendi kanalını görmemeli
        $response->assertDontSee('Benim Kanalım');

        // Diğer kullanıcının eklediği 3 kanal da görünmeli (sonuçta her kanal ayrı)
        $response->assertSee('Diğer Kanal 1');
        $response->assertSee('Diğer Kanal 2');
        $response->assertSee('Diğer Kanal 3');
    }

    public function test_user_can_follow_multiple_channels_from_same_creator(): void
    {
        $channelIds = [
            $this->otherChannels[0]->id,
            $this->otherChannels[1]->id,
            $this->otherChannels[2]->id,
        ];

        $response = $this->actingAs($this->me)
            ->post(route('discovery.follow', 'normal'), [
                'channel_ids' => $channelIds,
            ]);

        $response->assertRedirect(route('discovery.users', 'normal'));
        $response->assertSessionHas('status');

        $this->assertEquals(3, PlatformFollow::where('follower_user_id', $this->me->id)->count());
        foreach ($channelIds as $cid) {
            $this->assertTrue(
                PlatformFollow::where('follower_user_id', $this->me->id)
                    ->where('youtube_channel_id', $cid)
                    ->exists()
            );
        }
    }

    public function test_cannot_follow_less_than_3_channels(): void
    {
        $response = $this->actingAs($this->me)
            ->post(route('discovery.follow', 'normal'), [
                'channel_ids' => [$this->otherChannels[0]->id, $this->otherChannels[1]->id],
            ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_daily_follow_limit_is_strictly_enforced_at_3(): void
    {
        // İlk 3 kanal takip edildi
        PlatformFollow::create([
            'follower_user_id'   => $this->me->id,
            'followed_user_id'   => $this->otherUser->id,
            'youtube_channel_id' => $this->otherChannels[0]->id,
            'pool_type'          => 'normal',
            'created_at'         => now(),
        ]);
        PlatformFollow::create([
            'follower_user_id'   => $this->me->id,
            'followed_user_id'   => $this->otherUser->id,
            'youtube_channel_id' => $this->otherChannels[1]->id,
            'pool_type'          => 'normal',
            'created_at'         => now(),
        ]);
        PlatformFollow::create([
            'follower_user_id'   => $this->me->id,
            'followed_user_id'   => $this->otherUser->id,
            'youtube_channel_id' => $this->otherChannels[2]->id,
            'pool_type'          => 'normal',
            'created_at'         => now(),
        ]);

        // Günlük 3 hak dolduğu için 429 hatası vermeli
        $response = $this->actingAs($this->me)
            ->post(route('discovery.follow', 'normal'), [
                'channel_ids' => [
                    $this->otherChannels[0]->id,
                    $this->otherChannels[1]->id,
                    $this->otherChannels[2]->id,
                ],
            ]);

        $response->assertStatus(429);
    }

    public function test_user_with_multiple_channels_has_independent_daily_follow_tasks_per_channel(): void
    {
        $secondChannel = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_MY_CHANNEL_2',
            'title'              => 'İkinci Kanalım',
            'last_synced_at'     => now(),
        ]);

        UserChannelLink::create([
            'user_id'            => $this->me->id,
            'youtube_channel_id' => $secondChannel->id,
            'started_at'         => now(),
            'ended_at'           => now(),
        ]);

        // 1. kanal için günlük 3 takip görevi tamamlandı
        $this->actingAs($this->me)
            ->post(route('discovery.follow', 'normal'), [
                'source_channel_id' => $this->myChannel->id,
                'channel_ids'       => [
                    $this->otherChannels[0]->id,
                    $this->otherChannels[1]->id,
                    $this->otherChannels[2]->id,
                ],
            ])
            ->assertRedirect();

        // 1. kanal tekrar takip yapmak isterse 429 almalı
        $this->actingAs($this->me)
            ->post(route('discovery.follow', 'normal'), [
                'source_channel_id' => $this->myChannel->id,
                'channel_ids'       => [
                    $this->otherChannels[0]->id,
                    $this->otherChannels[1]->id,
                    $this->otherChannels[2]->id,
                ],
            ])
            ->assertStatus(429);

        // Ancak 2. kanal için günlük 3 takip görevi bağımsız olarak yapılabilmeli!
        $this->actingAs($this->me)
            ->post(route('discovery.follow', 'normal'), [
                'source_channel_id' => $secondChannel->id,
                'channel_ids'       => [
                    $this->otherChannels[0]->id,
                    $this->otherChannels[1]->id,
                    $this->otherChannels[2]->id,
                ],
            ])
            ->assertRedirect();

        $this->assertEquals(3, PlatformFollow::where('follower_channel_id', $this->myChannel->id)->count());
        $this->assertEquals(3, PlatformFollow::where('follower_channel_id', $secondChannel->id)->count());
    }
}

