<?php

namespace Tests\Integration;

use App\Models\ChannelPublish;
use Tests\Feature\ChannelPublishTest as ChannelPublishFeatureTest;
use Tests\TestCaseWithoutMiddleware;

class ChannelPublishTest extends TestCaseWithoutMiddleware
{
    const ID_NOT_VALID = 99999999;

    public function testAdd()
    {
        $newChannelPublish = factory(ChannelPublish::class)->make();
        $request = [
            'channel_id' => $newChannelPublish->channel_id
        ];
        $response = $this->json('POST', '/api/publishers/' . $newChannelPublish->publisher_id . '/channels', $request);
        $response->assertStatus(201);
        unset($newChannelPublish->created_at);
        unset($newChannelPublish->updated_at);
        $this->assertDatabaseHas('channel_publish', $newChannelPublish->toArray());
    }

    public function testShow()
    {
        $channelPublish = factory(ChannelPublish::class)->create();
        $response = $this->json('GET', '/api/publishers/' . $channelPublish->publisher_id . '/channels');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [ChannelPublishFeatureTest::getChannelPublishListJson()]
            ]);
    }

    public function testShowNotFound()
    {
        $response = $this->get('/api/subscribers/' . self::ID_NOT_VALID . '/channels');
        $response->assertStatus(404);
    }

    public function testDestroy()
    {
        $channelPublish = factory(ChannelPublish::class)->create();
        $response = $this->json(
            'DELETE',
            '/api/publishers/' . $channelPublish->publisher_id . '/channels/' . $channelPublish->channel_id
        );
        $response->assertStatus(200);
        $this->assertDatabaseMissing('channel_publish', [
            'id' => $channelPublish->id,
        ]);
    }
}
