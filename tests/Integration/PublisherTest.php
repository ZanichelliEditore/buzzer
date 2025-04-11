<?php

namespace Tests\Integration;

use App\Models\Publisher;
use Illuminate\Support\Str;
use Tests\TestCaseWithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\PublisherTest as PublisherFeatureTest;

class PublisherTest extends TestCaseWithoutMiddleware
{
    use DatabaseTransactions;

    const ID_NOT_VALID = 99999999;
    const REQUEST_ERROR_ARRAY = [
        'errors' => [
            'name',
            'host',
            'username',
        ]
    ];

    public function testAdd()
    {
        $newPublisher = factory(Publisher::class)->make();
        $response = $this->json('POST', '/api/publishers/', $newPublisher->toArray());
        $response->assertStatus(201);
        unset($newPublisher->created_at);
        unset($newPublisher->updated_at);
        unset($newPublisher->password);
        $this->assertDatabaseHas('publishers', $newPublisher->toArray());
    }

    public function testShow()
    {
        $publisher = factory(Publisher::class)->create();
        $response = $this->get('/api/publishers/' . $publisher->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => PublisherFeatureTest::getPublisherListJson()
            ]);
    }

    public function testShowNotFound()
    {
        $response = $this->get('/api/publishers/' . self::ID_NOT_VALID);
        $response->assertStatus(404);
    }

    public function testValidationError()
    {
        //Strings too long
        $response = $this->json('POST', '/api/publishers', [
            'name' => Str::random(51),
            'host' => Str::random(51),
            'username' => Str::random(51),
            'password' => 1
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(self::REQUEST_ERROR_ARRAY);

        //Request array empty
        $response = $this->json('POST', '/api/publishers', []);
        $response->assertStatus(422)
            ->assertJsonStructure(self::REQUEST_ERROR_ARRAY);

        //Wrong value type
        $response = $this->json('POST', '/api/publishers', [
            'name' => 1,
            'host' => [],
            'username' => 1,
            'password' => 1
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(self::REQUEST_ERROR_ARRAY);

        //Wrong host
        $response = $this->json('POST', '/api/publishers', [
            'name' => 'test-subscriber',
            'host' => 'test',
            'username' => 'username',
            'password' => 'password'
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'host'
                ]
            ]);

        //Duplicate host
        $response = $this->json('POST', '/api/publishers', [
            'name' => 'test-subscriber',
            'host' => 'http://test.zanichelli',
            'username' => 'username',
            'password' => 'password'
        ]);
        $response = $this->json('POST', '/api/publishers', [
            'name' => 'test-subscriber2',
            'host' => 'http://test.zanichelli',
            'username' => 'username',
            'password' => 'password'
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'host'
                ]
            ]);

        //Null value
        $response = $this->json('POST', '/api/publishers', [
            'name' => null,
            'host' => null,
            'username' => null,
            'password' => null
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(self::REQUEST_ERROR_ARRAY);
    }

    public function testDestroyNotFound()
    {
        $response = $this->json('DELETE', '/api/publishers/' . self::ID_NOT_VALID);
        $response->assertStatus(404);
    }

    public function testDestroy()
    {
        $publisher = factory(Publisher::class)->create();
        $response = $this->json('DELETE', '/api/publishers/' . $publisher->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('publishers', [
            'id' => $publisher->id,
        ]);
    }

    public function testlist()
    {
        factory(Publisher::class, 20)->create();

        $count = count(Publisher::all());
        $response = $this->get('/api/publishers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    PublisherFeatureTest::getPublisherListJson()
                ],
            ])
            ->assertJsonCount($count, 'data');

        $limit = 10;
        $response = $this->get("/api/publishers?limit={$limit}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    PublisherFeatureTest::getPublisherListJson()
                ],
            ])
            ->assertJsonCount($limit, 'data');
    }
}
