<?php

namespace Tests\Feature;

use Mockery;
use stdClass;
use App\Models\Channel;
use App\Models\Publisher;
use App\Models\Subscriber;
use Illuminate\Support\Str;
use Illuminate\Pagination\Paginator;
use App\Http\Repositories\ChannelRepository;
use Tests\TestCaseWithoutMiddleware;

class ChannelTest extends TestCaseWithoutMiddleware
{
    private function getChannelListJson()
    {
        return [
            "id",
            "name",
            "priority",
            "created_at",
            "updated_at"
        ];
    }

    private function getChannelDetailJson()
    {
        return [
            "id",
            "name",
            "priority",
            "created_at",
            "updated_at"
        ];
    }

    public function testShowSubscriber404Response()
    {
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('GET', '/api/channels/1/subscribers');
        $response->assertStatus(404);
    }

    public function testShowSubscriber200Response()
    {
        $channel = factory(Channel::class)->make();
        $channel->id = 1;
        $channel_copy = $channel;
        $subscriber = factory(Subscriber::class)->make();
        $subscriber->id = 1;
        unset($subscriber['created_at']);
        unset($subscriber['updated_at']);
        $obj = new stdClass();
        $obj->id = 1;
        $obj->endpoint = "test";
        $obj->username = "usertest";
        $obj->authentication = "NONE";
        $obj->subscriber = $subscriber;
        $obj->channel = $channel_copy;

        $channel->registrations = collect([$obj]);

        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $channel
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('GET', '/api/channels/1/subscribers');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'host',
                    'username',
                    'authentication',
                    'subscriber' => [
                        'id',
                        'name',
                        'host'
                    ],
                    'channel' => [
                        'id',
                        'name'
                    ]
                ]
            ]
        ]);
    }

    public function testShowPublisher404Response()
    {
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('GET', '/api/channels/1/publishers');
        $response->assertStatus(404);
    }

    public function testShowPublisher200Response()
    {
        $channel = factory(Channel::class)->make();
        $publisher = factory(Publisher::class)->make();
        $channel->publishers = collect([$publisher]);
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $channel
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('GET', '/api/channels/1/publishers');
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                [
                    'id' => $publisher->id,
                    'name' => $publisher->name,
                    'host' => $publisher->host,
                    'username' => $publisher->username,
                    'created_at' => $publisher->created_at->toDateTimeString(),
                    'updated_at' => $publisher->updated_at->toDateTimeString(),
                ]
            ]
        ]);
    }

    public function testList()
    {
        $channel = factory(Channel::class)->make();
        $channel->id = 1;
        $channel->priority = 'default';
        $channel->created_at = '2019-9-13 15:23:55';
        $channel->updated_at = '2019-9-13 15:23:55';
        $paginator = new Paginator([$channel], 12, 1, [1, 1, 1, 1]);
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'all' => $paginator
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->get('api/channels');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [$this->getChannelListJson()]]);
    }

    public function testListNotFoundAnyObject()
    {
        $paginator = new Paginator([], 12, 1, [1, 1, 1, 1]);
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'all' => $paginator
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->get('api/channels');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => []]);
    }

    public function testShow()
    {
        $channel = factory(Channel::class)->make();
        $channel->id = 9999999999;
        $channel->priority = 'default';
        $channel->created_at = '2019-9-13 15:23:55';
        $channel->updated_at = '2019-9-13 15:23:55';
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $channel
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->get('/api/channels/' . $channel->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => $this->getChannelDetailJson()]);
    }

    public function testShowNotFound()
    {
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->get('api/channels/1');
        $response->assertStatus(404);
    }

    public function testFailSave()
    {
        $request = [
            'name' => Str::random(10),
            'priority' => 'default'
        ];
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive('save')
            ->withAnyArgs()
            ->once()
            ->andThrow(new \Exception)
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);

        $response = $this->json('POST', 'api/channels', $request);
        $response->assertStatus(500);
    }

    public function testSave()
    {
        $request = [
            'name' => Str::random(10),
            'priority' => 'default'
        ];
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'save' => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);

        $response = $this->json('POST', 'api/channels', $request);
        $response->assertStatus(201);
    }

    public function testDestroyNotFound()
    {
        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('DELETE', 'api/channels/1');
        $response->assertStatus(404);
    }

    public function testSendMessageWithoutAuth()
    {
        $channel = factory(Channel::class)->make();

        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $channel,
                'delete' => false
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('DELETE', 'api/channels/' . 1);
        $response->assertStatus(500);
    }

    public function testFailDestroy()
    {
        $channel = factory(Channel::class)->make();

        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $channel,
                'delete' => false
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('DELETE', 'api/channels/' . 1);
        $response->assertStatus(500);
    }

    public function testDestroy()
    {
        $channel = factory(Channel::class)->make();

        $mock = Mockery::mock(ChannelRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $channel,
                'delete' => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\ChannelRepository', $mock);
        $response = $this->json('DELETE', 'api/channels/1');
        $response->assertStatus(200);
    }
}
