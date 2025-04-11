<?php

namespace Tests\Feature;

use App\Exceptions\DuplicateEntryException;
use Mockery;
use App\Models\Publisher;
use App\Models\ChannelPublish;
use App\Http\Repositories\PublisherRepository;
use App\Http\Repositories\ChannelPublishRepository;
use Tests\TestCaseWithoutMiddleware;

class ChannelPublishTest extends TestCaseWithoutMiddleware
{
    const ID_NOT_VALID = 99999999;

    public static function getChannelPublishDetailJson()
    {
        return [
            "id",
            "publisher",
            "channel"
        ];
    }

    public static function getChannelPublishListJson()
    {
        return [
            "id",
            "publisher",
            "channel",
        ];
    }

    public function testFailSave()
    {
        $channelPublish = factory(ChannelPublish::class)->make();
        $request = [
            'channel_id' => $channelPublish->channel_id
        ];
        $mock = Mockery::mock(ChannelPublishRepository::class)->makePartial()
            ->shouldReceive('save')
            ->withAnyArgs()
            ->once()
            ->andThrow(new \Exception)
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelPublishRepository', $mock);

        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'find' => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);

        $response = $this->json('POST', '/api/publishers/' . $channelPublish->publisher_id . '/channels', $request);
        $response->assertStatus(500);
    }

    public function testShowNoRegistration()
    {
        $subscriber = factory(Publisher::class)->make();
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $subscriber
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);
        $response = $this->get('/api/publishers/1/channels');
        $response->assertStatus(404);
    }

    public function testFailfindPublisher()
    {
        $channelPublish = factory(ChannelPublish::class)->make();
        $request = [
            'channel_id' => $channelPublish->channel_id
        ];

        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'find' => false
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);

        $response = $this->json('POST', '/api/publishers/1/channels', $request);
        $response->assertStatus(422);
    }

    public function testDuplicatePublisher()
    {
        $channelPublish = factory(ChannelPublish::class)->make();
        $request = [
            'channel_id' => $channelPublish->channel_id
        ];

        $mock = Mockery::mock(ChannelPublishRepository::class)->makePartial()
            ->shouldReceive([
                'save' => true
            ])
            ->withAnyArgs()
            ->once()
            ->andThrow(new DuplicateEntryException)
            ->getMock();

        $this->app->instance('App\Http\Repositories\ChannelPublishRepository', $mock);
        $response = $this->json('POST', '/api/publishers/1/channels', $request);
        $response->assertStatus(409);
    }

    public function testDestroyNotFound()
    {
        $mock = Mockery::mock(ChannelPublishRepository::class)->makePartial()
            ->shouldReceive([
                "getByFilter" => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelPublishRepository', $mock);
        $response = $this->json('DELETE', '/api/publishers/1/channels/1');
        $response->assertStatus(404);
    }

    public function testFailDestroy()
    {
        $channelPublish = factory(ChannelPublish::class)->make();
        $mock = Mockery::mock(ChannelPublishRepository::class)->makePartial()
            ->shouldReceive([
                'getByFilter' => $channelPublish,
                'delete' => false
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelPublishRepository', $mock);
        $response = $this->json('DELETE', '/api/publishers/1/channels/1');
        $response->assertStatus(500);
    }

    public function testDestroy()
    {
        $channelPublish = factory(ChannelPublish::class)->make();
        $mock = Mockery::mock(ChannelPublishRepository::class)->makePartial()
            ->shouldReceive([
                'getByFilter' => $channelPublish,
                'delete' => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelPublishRepository', $mock);
        $response = $this->json('DELETE', '/api/publishers/1/channels/1');
        $response->assertStatus(200);
    }
}
