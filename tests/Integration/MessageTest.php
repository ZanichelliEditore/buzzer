<?php

namespace Tests\Integration;

use Mockery;
use App\Models\Channel;
use App\Models\Publisher;
use Illuminate\Support\Str;
use App\Jobs\SendMessageJob;
use App\Models\ChannelPublish;
use App\Models\ChannelSubscribe;
use App\Http\Services\GuzzleService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Queue;
use Tests\CreatesApplication;

class MessageTest extends TestCase
{
    use CreatesApplication;

    const REQUEST_ERROR_ARRAY = [
        'errors' => [
            'name'
        ]
    ];

    const PUBLISHER_PASSWORD = 'password';

    public function testSendMessage()
    {
        Queue::fake();
        $channel = factory(Channel::class)->create();
        $publisher = factory(Publisher::class)->create(['password' => bcrypt(self::PUBLISHER_PASSWORD)]);
        factory(ChannelPublish::class)->create(['channel_id' => $channel->id, 'publisher_id' => $publisher->id]);
        factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id]);
        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $authorization = [
            'PHP_AUTH_USER' => $publisher->username,
            'PHP_AUTH_PW' => self::PUBLISHER_PASSWORD
        ];
        $response = $this->json('POST', '/api/sendMessage', $request, $authorization);
        $response->assertStatus(200);
        $response = $this->json('POST', '/api/sendMessage/' . $channel->name, $request, $authorization);
        $response->assertStatus(200);
        Queue::assertPushed(SendMessageJob::class, 2);
    }

    public function testSendMessageWithBasicAuth()
    {
        $channel = factory(Channel::class)->create();
        $publisher = factory(Publisher::class)->create(['password' => bcrypt(self::PUBLISHER_PASSWORD)]);
        factory(ChannelPublish::class)->create(['channel_id' => $channel->id, 'publisher_id' => $publisher->id]);
        factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id, 'authentication' => 'BASIC']);
        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $authorization = [
            'PHP_AUTH_USER' => $publisher->username,
            'PHP_AUTH_PW' => self::PUBLISHER_PASSWORD
        ];
        $mock = Mockery::mock(GuzzleService::class)->makePartial()
            ->shouldReceive(['sendWithBasicAuth' => null])
            ->withAnyArgs()
            ->twice()
            ->getMock();
        $this->app->instance('App\Http\Services\GuzzleService', $mock);

        $response = $this->json('POST', '/api/sendMessage', $request, $authorization);
        $response->assertStatus(200);
        $response = $this->json('POST', '/api/sendMessage/' . $channel->name, $request, $authorization);
        $response->assertStatus(200);
    }

    public function testSendMessageWithoutAuth()
    {
        $channel = factory(Channel::class)->create();
        $publisher = factory(Publisher::class)->create(['password' => bcrypt(self::PUBLISHER_PASSWORD)]);
        factory(ChannelPublish::class)->create(['channel_id' => $channel->id, 'publisher_id' => $publisher->id]);
        factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id, 'authentication' => 'NONE']);
        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $authorization = [
            'PHP_AUTH_USER' => $publisher->username,
            'PHP_AUTH_PW' => self::PUBLISHER_PASSWORD
        ];
        $mock = Mockery::mock(GuzzleService::class)->makePartial()
            ->shouldReceive(['sendWithoutAuth' => null])
            ->withAnyArgs()
            ->twice()
            ->getMock();
        $this->app->instance('App\Http\Services\GuzzleService', $mock);

        $response = $this->json('POST', '/api/sendMessage', $request, $authorization);
        $response->assertStatus(200);

        $response = $this->json('POST', '/api/sendMessage/' . $channel->name, $request, $authorization);
        $response->assertStatus(200);
    }

    public function testDoubleSubscription()
    {
        Queue::fake();
        $channel = factory(Channel::class)->create();
        $publisher = factory(Publisher::class)->create(['password' => bcrypt(self::PUBLISHER_PASSWORD)]);
        $channelPublish = factory(ChannelPublish::class)->create(['channel_id' => $channel->id, 'publisher_id' => $publisher->id]);
        $channelsubscribe = factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id]);
        factory(ChannelSubscribe::class)->create(['channel_id' => $channel->id, 'subscriber_id' => $channelsubscribe->subscriber_id]);
        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $authorization = [
            'PHP_AUTH_USER' => $publisher->username,
            'PHP_AUTH_PW' => self::PUBLISHER_PASSWORD
        ];
        $response = $this->json('POST', '/api/sendMessage', $request, $authorization);
        $response->assertStatus(200);
        $response = $this->json('POST', '/api/sendMessage/' . $channel->name, $request, $authorization);
        $response->assertStatus(200);
        Queue::assertPushed(SendMessageJob::class, 4);
    }

    public function testSendMessagePublisherNotHasChannel()
    {
        $channel = factory(Channel::class)->create();
        $publisher = factory(Publisher::class)->create(['password' => bcrypt(self::PUBLISHER_PASSWORD)]);

        $request = [
            "channel" => $channel->name,
            "message" => "test"
        ];
        $authorization = [
            'PHP_AUTH_USER' => $publisher->username,
            'PHP_AUTH_PW' => self::PUBLISHER_PASSWORD
        ];

        $response = $this->json('POST', '/api/sendMessage', $request, $authorization);
        $response->assertStatus(403)
            ->assertJson([
                'message' => "Not authorized to send message on requested channel"
            ]);
        $response = $this->json('POST', '/api/sendMessage/' . $channel->name, $request, $authorization);
        $response->assertStatus(403)
            ->assertJson([
                'message' => "Not authorized to send message on requested channel"
            ]);
    }

    public function testBasicAuthMissingCredentials()
    {
        $authorization = [
            'PHP_AUTH_USER' => '',
            'PHP_AUTH_PW' => ''
        ];
        $response = $this->json('POST', '/api/sendMessage', [], $authorization);
        $response->assertStatus(401);

        $response = $this->json('POST', '/api/sendMessage/' . Str::random(10), [], $authorization);
        $response->assertStatus(401);
    }

    public function testBasicAuthMissingPublisherUsername()
    {
        $authorization = [
            'PHP_AUTH_USER' => Str::random(50),
            'PHP_AUTH_PW' => Str::random(50)
        ];
        $response = $this->json('POST', '/api/sendMessage', [], $authorization);
        $response->assertStatus(401);
        $response = $this->json('POST', '/api/sendMessage/' . Str::random(10), [], $authorization);
        $response->assertStatus(401);
    }

    public function testBasicAuthFailedAuth()
    {
        $publisher = factory(Publisher::class)->create(['password' => bcrypt(self::PUBLISHER_PASSWORD)]);

        $authorization = [
            'PHP_AUTH_USER' => $publisher->username,
            'PHP_AUTH_PW' => Str::random(50)
        ];
        $response = $this->json('POST', '/api/sendMessage', [], $authorization);
        $response->assertStatus(401);

        $response = $this->json('POST', '/api/sendMessage/' . Str::random(10), [], $authorization);
        $response->assertStatus(401);
    }
}
