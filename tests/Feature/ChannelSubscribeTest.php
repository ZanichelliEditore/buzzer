<?php

namespace Tests\Feature;

use App\Exceptions\DuplicateEntryException;
use Mockery;
use App\Models\Subscriber;
use App\Models\ChannelSubscribe;
use App\Http\Repositories\SubscriberRepository;
use App\Http\Repositories\ChannelSubscribeRepository;
use Exception;
use Tests\TestCaseWithoutMiddleware;

class ChannelSubscribeTest extends TestCaseWithoutMiddleware
{
    public static function getChannelSubscribeDetailJson()
    {
        return [
            "id",
            "endpoint",
            "authentication",
            "subscriber",
            "channel",
        ];
    }

    /**
     * @test
     * @return void
     */
    public function failSaveTest()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->make();
        $request = [
            'channel_id' => $channelSubscribe->channel_id,
            'endpoint' => $channelSubscribe->endpoint,
            'authentication' => $channelSubscribe->authentication,
            'username' => $channelSubscribe->username,
            'password' => $channelSubscribe->password
        ];
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                'find' => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $mock = Mockery::mock(ChannelSubscribeRepository::class)->makePartial()
            ->shouldReceive('save')
            ->withAnyArgs()
            ->once()
            ->andThrow(new \Exception)
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelSubscribeRepository', $mock);
        $response = $this->json('POST', '/api/subscribers/' . $channelSubscribe->subscriber_id . '/channels', $request);
        $response->assertStatus(500);
    }

    /**
     * @test
     * @return void
     */
    public function failCreateNotFoundTest()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->make();
        $request = [
            'channel_id' => $channelSubscribe->channel_id,
            'endpoint' => $channelSubscribe->endpoint,
            'authentication' => $channelSubscribe->authentication,
            'username' => $channelSubscribe->username,
            'password' => $channelSubscribe->password
        ];
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                'find' => false
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $response = $this->json('POST', '/api/subscribers/' . $channelSubscribe->subscriber_id . '/channels', $request);
        $response->assertStatus(422);
    }

    /**
     * @test
     * @return void
     */
    public function duplicateSubscribetTest()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->make();
        $request = [
            'channel_id' => $channelSubscribe->channel_id,
            'endpoint' => $channelSubscribe->endpoint,
            'authentication' => $channelSubscribe->authentication,
            'username' => $channelSubscribe->username,
            'password' => $channelSubscribe->password
        ];

        $mock = Mockery::mock(ChannelSubscribeRepository::class)->makePartial()
            ->shouldReceive([
                'save' => true
            ])
            ->withAnyArgs()
            ->once()
            ->andThrow(new DuplicateEntryException)
            ->getMock();

        $this->app->instance('App\Http\Repositories\ChannelSubscribeRepository', $mock);
        $response = $this->json('POST', '/api/subscribers/1/channels', $request);
        $response->assertStatus(409);
    }
    /**
     * @test
     * @return void
     */
    public function showNoSubscriptionsTest()
    {
        $subscriber = factory(Subscriber::class)->make();
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $subscriber
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $response = $this->get('/api/subscribers/1/channels');
        $response->assertStatus(404);
    }

    /**
     * @test
     * @return void
     */
    public function destroyNotFound()
    {
        $mock = Mockery::mock(ChannelSubscribeRepository::class)->makePartial()
            ->shouldReceive([
                "find" => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelSubscribeRepository', $mock);
        $response = $this->json('DELETE', '/api/channel-subscriber/123456');
        $response->assertStatus(404);
    }

    /**
     * @test
     * @return void
     */
    public function failDestroyTest()
    {
        $mock = Mockery::mock(ChannelSubscribeRepository::class)->makePartial()
            ->shouldReceive('find')
            ->andThrow(new Exception())
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelSubscribeRepository', $mock);
        $response = $this->json('DELETE', '/api/channel-subscriber/1');
        $response->assertStatus(500);
    }

    /**
     * @test
     * @return void
     */
    public function destroyTest()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->make();
        $mock = Mockery::mock(ChannelSubscribeRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $channelSubscribe,
                'delete' => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelSubscribeRepository', $mock);

        $response = $this->json('DELETE', "/api/channel-subscriber/1");
        $response->assertStatus(200);
    }
}
