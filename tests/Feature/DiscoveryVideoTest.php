<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryVideoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $creator;
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

        $this->creator = User::create([
            'name'       => 'Üretici Kullanıcı',
            'email'      => 'uretici@example.com',
            'username'   => 'ureticikullanici',
            'google_sub' => 'sub_creator_1',
            'status'     => 'active',
        ]);

        $this->channel = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_TEST_DISC_CHANNEL',
            'title'              => 'Keşif Kanalı',
            'last_synced_at'     => now(),
        ]);

        UserChannelLink::create([
            'user_id'            => $this->creator->id,
            'youtube_channel_id' => $this->channel->id,
            'started_at'         => now(),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $v = Video::create([
                'youtube_video_id'   => 'vid_disc_' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'youtube_channel_id' => $this->channel->id,
                'title'              => 'Keşif Videosu ' . $i,
                'availability'       => 'available',
                'published_at'       => now()->subDays($i),
            ]);
            $this->videos[] = $v;

            // Üretici bu videoyu vitrinine ekleyip havuza koydu
            UserVideoSelection::create([
                'user_id'            => $this->creator->id,
                'video_id'           => $v->id,
                'youtube_channel_id' => $this->channel->id,
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

    public function test_user_never_sees_their_own_videos_in_discovery(): void
    {
        // Üretici kullanıcının kendi hesabıyla keşfe girdiğinde, kendi videolarını ASLA görememeli
        $response = $this->actingAs($this->creator)->get(route('discovery.videos'));

        $response->assertStatus(200);
        $response->assertDontSee('Keşif Videosu 1');
        $response->assertDontSee('Keşif Videosu 2');
        $response->assertSee('Henüz Keşif Videosu Bulunmuyor');
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

    public function test_user_with_multiple_channels_sees_other_channels_videos_except_active_channel(): void
    {
        // Kullanıcı 2 kanala sahip: Kanal A (Aktif), Kanal B (Alternatif)
        $multiUser = User::create([
            'name'       => 'Çoklu Kanal Sahibi',
            'email'      => 'coklu@example.com',
            'username'   => 'coklukanal',
            'google_sub' => 'sub_multi_ch',
            'status'     => 'active',
        ]);

        $channelA = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_MULTI_CH_A',
            'title'              => 'Kanal A (Aktif)',
            'last_synced_at'     => now(),
        ]);

        $channelB = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_MULTI_CH_B',
            'title'              => 'Kanal B (Alternatif)',
            'last_synced_at'     => now(),
        ]);

        // Kanal A aktif bağ
        UserChannelLink::create([
            'user_id'            => $multiUser->id,
            'youtube_channel_id' => $channelA->id,
            'started_at'         => now(),
            'ended_at'           => null,
        ]);

        // Kanal B eski/diğer bağ
        UserChannelLink::create([
            'user_id'            => $multiUser->id,
            'youtube_channel_id' => $channelB->id,
            'started_at'         => now()->subDays(1),
            'ended_at'           => now()->subMinutes(10),
        ]);

        $videoA = Video::create([
            'youtube_video_id'   => 'vid_multi_a_1',
            'youtube_channel_id' => $channelA->id,
            'title'              => 'Kanal A Özel Videosu',
            'availability'       => 'available',
        ]);
        UserVideoSelection::create([
            'user_id'            => $multiUser->id,
            'video_id'           => $videoA->id,
            'youtube_channel_id' => $channelA->id,
        ]);

        $videoB = Video::create([
            'youtube_video_id'   => 'vid_multi_b_1',
            'youtube_channel_id' => $channelB->id,
            'title'              => 'Kanal B Vitrin Videosu',
            'availability'       => 'available',
        ]);
        UserVideoSelection::create([
            'user_id'            => $multiUser->id,
            'video_id'           => $videoB->id,
            'youtube_channel_id' => $channelB->id,
        ]);

        // Kanal A aktifken keşfe girildiğinde:
        // Kanal A videosu görünmez, Kanal B videosu GÖRÜNÜR!
        $response = $this->actingAs($multiUser)->get(route('discovery.videos'));
        $response->assertStatus(200);
        $response->assertDontSee('Kanal A Özel Videosu');
        $response->assertSee('Kanal B Vitrin Videosu');
    }
}
