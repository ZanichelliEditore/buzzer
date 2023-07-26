<?php

namespace Tests\Integration;

use App\Models\ChannelSubscribe;
use Tests\Feature\ChannelSubscribeTest as ChannelSubscribeFeatureTest;
use Tests\TestCaseWithoutMiddleware;

class ChannelSubscribeTest extends TestCaseWithoutMiddleware
{
    const ID_NOT_VALID = 99999999;

    public function getEndpoint()
    {
        return [
            ['example/endpoint', 'example/endpoint'],
            ['/example/endpoint', 'example/endpoint'],
            ['///example/endpoint', 'example/endpoint']
        ];
    }

    /**
     * @test
     * @dataProvider getEndpoint
     * @return void
     */
    public function addTest($endpoint, $expectedEndpoint)
    {
        $newChannelSubscribe = factory(ChannelSubscribe::class)->make(['endpoint' => $endpoint]);

        $response = $this->json(
            'POST',
            '/api/subscribers/' . $newChannelSubscribe->subscriber_id . '/channels',
            $newChannelSubscribe->toArray()
        );
        $response->assertStatus(201);
        unset($newChannelSubscribe->created_at);
        unset($newChannelSubscribe->updated_at);
        unset($newChannelSubscribe->password);

        $expectedData = $newChannelSubscribe->toArray();
        $expectedData['endpoint'] = $expectedEndpoint;
        $this->assertDatabaseHas('channel_subscribe', $expectedData);
    }

    /**
     * @test
     * @return void
     */
    public function addDuplicateTest()
    {
        $newChannelSubscribe = factory(ChannelSubscribe::class)->make();
        $response = $this->json('POST', '/api/subscribers/' . $newChannelSubscribe->subscriber_id . '/channels', $newChannelSubscribe->toArray());
        $response->assertStatus(201);
        unset($newChannelSubscribe->created_at);
        unset($newChannelSubscribe->updated_at);
        unset($newChannelSubscribe->password);
        $this->assertDatabaseHas('channel_subscribe', $newChannelSubscribe->toArray());

        $newChannelSubscribe->authentication = 'NONE';
        $response = $this->json('POST', '/api/subscribers/' . $newChannelSubscribe->subscriber_id . '/channels', $newChannelSubscribe->toArray());
        $response->assertStatus(409);
    }

    /**
     * @test
     * @return void
     */
    public function showTest()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->create();
        $response = $this->get('/api/subscribers/' . $channelSubscribe->subscriber_id . '/channels');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [ChannelSubscribeFeatureTest::getChannelSubscribeDetailJson()]
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function showNotFoundTest()
    {
        $response = $this->get('/api/subscribers/' . self::ID_NOT_VALID . '/channels');
        $response->assertStatus(404);
    }

    /**
     * @test
     * @return void
     */
    public function destroyTest()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->create();
        $response = $this->json(
            'DELETE',
            '/api/channel-subscriber/' . $channelSubscribe->id
        );
        $response->assertStatus(200);
        $this->assertDatabaseMissing('channel_subscribe', [
            'id' => $channelSubscribe->id,
        ]);
    }
}
