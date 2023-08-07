<?php

namespace Tests\Integration;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Feature\SubscriberTest as SubscriberFeatureTest;
use Tests\TestCaseWithoutMiddleware;

class SubscriberTest extends TestCaseWithoutMiddleware
{
    use DatabaseTransactions;

    const ID_NOT_VALID = 99999999;
    const REQUEST_ERROR_ARRAY = [
        'errors' => [
            'name',
            'host'
        ]
    ];

    /**
     * @test
     * @return void
     */
    public function validationErrorTest()
    {
        //Strings too long
        $response = $this->json('POST', '/api/subscribers', [
            'name' => Str::random(51),
            'host' => "https://" . Str::random(140) . ".it",
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'name',
                    'host'
                ]
            ]);

        //Request array empty
        $response = $this->json('POST', '/api/subscribers', []);
        $response->assertStatus(422)
            ->assertJsonStructure(self::REQUEST_ERROR_ARRAY);

        //Wrong value type
        $response = $this->json('POST', '/api/subscribers', [
            'name' => 1,
            'host' => []
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(self::REQUEST_ERROR_ARRAY);

        //Wrong host
        $response = $this->json('POST', '/api/subscribers', [
            'name' => 'test-subscriber',
            'host' => 'test'
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'host'
                ]
            ]);

        //Duplicate host
        $response = $this->json('POST', '/api/subscribers', [
            'name' => 'test-subscriber',
            'host' => 'http://test.zanichelli'
        ]);
        $response = $this->json('POST', '/api/subscribers', [
            'name' => 'test-subscriber2',
            'host' => 'http://test.zanichelli'
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'host'
                ]
            ]);

        //Null value
        $response = $this->json('POST', '/api/subscribers', [
            'name' => null,
            'host' => null
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(self::REQUEST_ERROR_ARRAY);
    }

    /**
     * @test
     * @return void
     */
    public function listTest()
    {
        factory(Subscriber::class, 20)->create();

        $count = count(Subscriber::all());
        $response = $this->get('/api/subscribers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    SubscriberFeatureTest::getSubscriberListJson()
                ],
            ])
            ->assertJsonCount($count, 'data');

        $limit = 10;
        $response = $this->get("/api/subscribers?limit={$limit}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    SubscriberFeatureTest::getSubscriberListJson()
                ],
            ])
            ->assertJsonCount($limit, 'data');
    }

    /**
     * @test
     * @return void
     */
    public function showTest()
    {
        $subscriber = factory(Subscriber::class)->create();
        $response = $this->get('/api/subscribers/' . $subscriber->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => SubscriberFeatureTest::getSubscriberListJson()
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function showNotFoundTest()
    {
        $response = $this->get('/api/subscribers/' . self::ID_NOT_VALID);
        $response->assertStatus(404);
    }

    /**
     * @test
     * @return void
     */
    public function destroyNotFoundTest()
    {
        $response = $this->json('DELETE', '/api/subscribers/' . self::ID_NOT_VALID);
        $response->assertStatus(404);
    }

    /**
     * @test
     * @return void
     */
    public function destroyTest()
    {
        $subscriber = factory(Subscriber::class)->create();
        $response = $this->json('DELETE', '/api/subscribers/' . $subscriber->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('subscribers', [
            'id' => $subscriber->id,
        ]);
    }

    static function getHost()
    {
        return [
            ['https://example.com', 'https://example.com/'],
            ['https://example.com/', 'https://example.com/'],
            ['https://example.com///', 'https://example.com/']
        ];
    }

    /**
     * @test
     * @dataProvider getHost
     * @return void
     */
    public function addTest($host, $expectedHost)
    {
        $newSubscriber = factory(Subscriber::class)->make(['host' => $host]);
        $response = $this->json('POST', '/api/subscribers/', $newSubscriber->toArray());

        $response->assertStatus(201);
        unset($newSubscriber->created_at);
        unset($newSubscriber->updated_at);

        $expecteData = $newSubscriber->toArray();
        $expecteData['host'] = $expectedHost;

        $this->assertDatabaseHas('subscribers', $expecteData);
    }
}
