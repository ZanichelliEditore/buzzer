<?php

namespace Tests\Feature;

use Mockery;
use App\Models\Channel;
use Illuminate\Support\Str;
use App\Jobs\SendMessageJob;
use App\Models\ChannelSubscribe;
use App\Http\Services\GuzzleService;
use Illuminate\Support\Facades\Queue;
use App\Http\Repositories\PublisherRepository;
use Tests\TestCaseWithoutMiddleware;

class MessageWithoutMiddlewareTest extends TestCaseWithoutMiddleware
{
    /**
     * @test
     * @return void
     */
    public function SendMessageNoDataTest()
    {
        Queue::fake();
        $request = [
            "channel" => "",
            "message" => ""
        ];
        $response = $this->json('POST', '/api/sendMessage', $request);
        $response->assertStatus(422)
            ->assertJsonStructure(
                [
                    'errors' =>
                    [
                        "channel",
                        "message"
                    ],
                ]
            );
        $response = $this->json('POST', '/api/sendMessage/' . Str::random(10), $request);
        $response->assertStatus(422)
            ->assertJsonStructure(
                [
                    'errors' =>
                    [
                        "message"
                    ],
                ]
            );
        Queue::assertNotPushed(SendMessageJob::class);
    }

    /**
     * @test
     * @return void
     */
    public function SendMessageChannelNotExistTest()
    {
        Queue::fake();
        $request = [
            "channel" => Str::random(50),
            "message" => "test"
        ];
        $response = $this->json('POST', '/api/sendMessage', $request);
        $response->assertStatus(422)
            ->assertJsonStructure(
                [
                    'errors' =>
                    [
                        "channel",
                    ],
                ]
            );
        $response = $this->json('POST', '/api/sendMessage/' . Str::random(10), $request);
        $response->assertStatus(404);
        Queue::assertNotPushed(SendMessageJob::class);
    }

    /**
     * @test
     * @return void
     */
    public function SendMessageDataNotValidTest()
    {
        Queue::fake();
        $request = [
            "channel" => 1,
            "message" => []
        ];
        $response = $this->json('POST', '/api/sendMessage', $request);
        $response->assertStatus(422)
            ->assertJsonStructure(
                [
                    'errors' =>
                    [
                        "channel",
                        "message"
                    ],
                ]
            );
        $response = $this->json('POST', '/api/sendMessage/' . Str::random(10), $request);
        $response->assertStatus(422)
            ->assertJsonStructure(
                [
                    'errors' =>
                    [
                        "message"
                    ],
                ]
            );
        Queue::assertNotPushed(SendMessageJob::class);
    }

    /**
     * @test
     * @return void
     */
    public function SendMessageTest()
    {
        Queue::fake();
        $channel = factory(Channel::class)->create();
        factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id]);
        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $mockHasChannel = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'hasChannel' => true
            ])
            ->withAnyArgs()
            ->twice()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mockHasChannel);

        $response = $this->json('POST', '/api/sendMessage', $request);
        $response->assertStatus(200);
        $response = $this->json('POST', '/api/sendMessage/' . $channel->name, $request);
        $response->assertStatus(200);
        Queue::assertPushed(SendMessageJob::class, 2);
    }

    /**
     * @test
     * @return void
     */
    public function DoubleSubscriptionTest()
    {
        Queue::fake();
        $channel = factory(Channel::class)->create();
        $channelsubscribe = factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id]);
        factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id, 'subscriber_id' => $channelsubscribe->subscriber_id]);
        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $mockHasChannel = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'hasChannel' => true
            ])
            ->withAnyArgs()
            ->twice()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mockHasChannel);
        $response = $this->json('POST', '/api/sendMessage', $request);
        $response->assertStatus(200);
        $response = $this->json('POST', '/api/sendMessage/' . $channel->name, $request);
        $response->assertStatus(200);
        Queue::assertPushed(SendMessageJob::class, 4);
    }

    /**
     * @test
     * @return void
     */
    public function SendMessageWithOAuth2Test()
    {
        $channel = factory(Channel::class)->create();
        factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id, 'authentication' => 'OAUTH2']);
        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $mockHasChannel = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'hasChannel' => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mockHasChannel);
        $mock = Mockery::mock(GuzzleService::class)->makePartial()
            ->shouldReceive(['sendWithOAuth2' => null])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Services\GuzzleService', $mock);

        $response = $this->json('POST', '/api/sendMessage', $request);
        $response->assertStatus(200);
    }
}
