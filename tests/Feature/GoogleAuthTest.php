<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserChannelLink;
use App\Models\UserVideoSelection;
use App\Models\Video;
use App\Models\YouTubeChannel;
use App\Services\YouTubeChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_returns_valid_oauth_url(): void
    {
        config(['services.google.client_id' => 'test-client-id.apps.googleusercontent.com']);
        config(['services.google.redirect' => 'https://youtube.aiajan.com.tr/auth/google/callback']);

        $response = $this->get(route('auth.google'));

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('https://accounts.google.com/o/oauth2/v2/auth', $location);
        $this->assertStringContainsString('client_id=test-client-id.apps.googleusercontent.com', $location);
        $this->assertStringContainsString('youtube.readonly', $location);
    }

    public function test_firebase_login_with_multiple_channels_imports_all_channels_and_redirects_to_selection(): void
    {
        $mockChannelsService = Mockery::mock(YouTubeChannelService::class);
        $mockChannelsService->shouldReceive('assertConfigured')->andReturnNull();
        $mockChannelsService->shouldReceive('listMine')->andReturn(collect([
            [
                'youtube_channel_id' => 'UC_MULTI_1',
                'title'              => 'Birinci Kanalım',
                'thumbnail_url'      => 'https://i.ytimg.com/vi/1.jpg',
                'custom_url'         => '@kanalbir',
            ],
            [
                'youtube_channel_id' => 'UC_MULTI_2',
                'title'              => 'İkinci Kanalım',
                'thumbnail_url'      => 'https://i.ytimg.com/vi/2.jpg',
                'custom_url'         => '@kanaliki',
            ],
            [
                'youtube_channel_id' => 'UC_MULTI_3',
                'title'              => 'Üçüncü Kanalım',
                'thumbnail_url'      => 'https://i.ytimg.com/vi/3.jpg',
                'custom_url'         => '@kanaluc',
            ],
        ]));
        $mockChannelsService->shouldReceive('syncChannelVideos')->andReturn(10);
        $this->app->instance(YouTubeChannelService::class, $mockChannelsService);

        // Firebase verify token mock'u için controller'ın protected metodunu mocklayalım veya test edelim
        $controller = $this->getMockBuilder(\App\Http\Controllers\Auth\GoogleAuthController::class)
            ->onlyMethods(['verifyFirebaseToken'])
            ->getMock();

        $controller->method('verifyFirebaseToken')->willReturn([
            'uid'   => 'google_sub_multi_123',
            'email' => 'creator@example.com',
            'name'  => 'Emrah İçerik Üretici',
            'photo' => 'https://lh3.googleusercontent.com/avatar.jpg',
        ]);
        $this->app->instance(\App\Http\Controllers\Auth\GoogleAuthController::class, $controller);

        $response = $this->postJson(route('auth.firebase.callback'), [
            'idToken'     => 'mock_id_token',
            'accessToken' => 'mock_access_token',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'redirect' => route('channels.select'),
        ]);

        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertEquals('creator@example.com', $user->email);

        // 3 kanalın tamamı veritabanına aktarıldı mı?
        $this->assertDatabaseHas('youtube_channels', ['youtube_channel_id' => 'UC_MULTI_1']);
        $this->assertDatabaseHas('youtube_channels', ['youtube_channel_id' => 'UC_MULTI_2']);
        $this->assertDatabaseHas('youtube_channels', ['youtube_channel_id' => 'UC_MULTI_3']);

        // Kullanıcının kayıtlı kanal listesinde 3 kanal da var mı?
        $this->assertCount(3, $user->fresh()->google_channel_ids);

        // İlk kanal aktif kanal olarak bağlandı mı?
        $activeLink = $user->fresh()->activeChannelLink;
        $this->assertNotNull($activeLink);
        $this->assertEquals('UC_MULTI_1', $activeLink->channel->youtube_channel_id);

        // Diğer kanallar da kullanıcının kanal geçmişine kaydedildi mi?
        $allLinks = $user->channelLinks()->get();
        $this->assertCount(3, $allLinks);
    }

    public function test_firebase_login_with_single_channel_redirects_to_showcase_when_vitrin_empty(): void
    {
        $mockChannelsService = Mockery::mock(YouTubeChannelService::class);
        $mockChannelsService->shouldReceive('assertConfigured')->andReturnNull();
        $mockChannelsService->shouldReceive('listMine')->andReturn(collect([
            [
                'youtube_channel_id' => 'UC_SINGLE_CH',
                'title'              => 'Tekil Kanalım',
                'thumbnail_url'      => 'https://i.ytimg.com/vi/single.jpg',
                'custom_url'         => '@tekilkanal',
            ],
        ]));
        $mockChannelsService->shouldReceive('syncChannelVideos')->andReturn(10);
        $this->app->instance(YouTubeChannelService::class, $mockChannelsService);

        $controller = $this->getMockBuilder(\App\Http\Controllers\Auth\GoogleAuthController::class)
            ->onlyMethods(['verifyFirebaseToken'])
            ->getMock();

        $controller->method('verifyFirebaseToken')->willReturn([
            'uid'   => 'google_sub_single_999',
            'email' => 'single@example.com',
            'name'  => 'Tekil Kanal Sahibi',
            'photo' => null,
        ]);
        $this->app->instance(\App\Http\Controllers\Auth\GoogleAuthController::class, $controller);

        $response = $this->postJson(route('auth.firebase.callback'), [
            'idToken'     => 'mock_id_token',
            'accessToken' => 'mock_access_token',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'redirect' => route('videos.showcase'),
        ]);

        $user = auth()->user();
        $this->assertNotNull($user->fresh()->activeChannelLink);
        $this->assertEquals('UC_SINGLE_CH', $user->fresh()->activeChannelLink->channel->youtube_channel_id);
    }

    public function test_user_can_switch_to_other_imported_channel_and_select_showcase(): void
    {
        $user = User::create([
            'name'       => 'Çoklu Kanal Üyesi',
            'email'      => 'multi@example.com',
            'username'   => 'multiuser',
            'google_sub' => 'sub_multi_99',
            'status'     => 'active',
            'google_channel_ids' => ['UC_CH_A', 'UC_CH_B'],
        ]);

        $chanA = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_CH_A',
            'title'              => 'Kanal A',
            'is_active'          => true,
        ]);

        $chanB = YouTubeChannel::create([
            'youtube_channel_id' => 'UC_CH_B',
            'title'              => 'Kanal B',
            'is_active'          => true,
        ]);

        // Aktif kanal olarak Kanal A bağlı
        UserChannelLink::create([
            'user_id'            => $user->id,
            'youtube_channel_id' => $chanA->id,
            'started_at'         => now(),
        ]);

        // Kanal B de geçmiş bağ olarak kayıtlı
        UserChannelLink::create([
            'user_id'            => $user->id,
            'youtube_channel_id' => $chanB->id,
            'started_at'         => now(),
            'ended_at'           => now(),
        ]);

        // Kanal B için 3 video oluştur
        $v1 = Video::create(['youtube_video_id' => 'v_b_1', 'youtube_channel_id' => $chanB->id, 'title' => 'Video B1', 'availability' => 'available']);
        $v2 = Video::create(['youtube_video_id' => 'v_b_2', 'youtube_channel_id' => $chanB->id, 'title' => 'Video B2', 'availability' => 'available']);
        $v3 = Video::create(['youtube_video_id' => 'v_b_3', 'youtube_channel_id' => $chanB->id, 'title' => 'Video B3', 'availability' => 'available']);

        // Kullanıcı Kanal B'yi yönetmek için seçer
        $switchResponse = $this->actingAs($user)->post(route('channels.store'), [
            'channel_id' => 'UC_CH_B',
        ]);

        $switchResponse->assertRedirect(route('videos.showcase'));
        $this->assertEquals($chanB->id, $user->fresh()->activeChannelLink->youtube_channel_id);

        // Kullanıcı Kanal B için vitrin videolarını günceller
        $vitrinResponse = $this->actingAs($user)->post(route('videos.showcase.update'), [
            'video_ids' => [$v1->id, $v2->id, $v3->id],
        ]);

        $vitrinResponse->assertRedirect(route('videos.showcase'));
        $this->assertCount(3, UserVideoSelection::where('user_id', $user->id)->where('youtube_channel_id', $chanB->id)->get());
    }
}
