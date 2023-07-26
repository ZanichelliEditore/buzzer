<?php

namespace Tests\Integration;

use App\Models\Publisher;
use App\Models\Subscriber;
use App\Models\ChannelPublish;
use App\Models\ChannelSubscribe;
use Tests\TestCaseWithoutMiddleware;

class ChannelTest extends TestCaseWithoutMiddleware
{
    /**
     * @test
     * @return void
     */
    public function showSubscribersTest()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->create();
        $subscriber = Subscriber::find($channelSubscribe->subscriber_id);
        unset($subscriber['created_at']);
        unset($subscriber['updated_at']);
        unset($channelSubscribe->channel['created_at']);
        unset($channelSubscribe->channel['updated_at']);
        $response = $this->json('GET', '/api/channels/' . $channelSubscribe->channel_id . '/subscribers');
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

    /**
     * @test
     * @return void
     */
    public function showPublishersTest()
    {
        $channelPublish = factory(ChannelPublish::class)->create();
        $publisher = Publisher::find($channelPublish->publisher_id);
        $response = $this->json('GET', '/api/channels/' . $channelPublish->channel_id . '/publishers');
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
}
