<?php

namespace Tests\Feature;

use Mockery;
use App\Models\Publisher;
use App\Http\Repositories\PublisherRepository;
use Tests\TestCaseWithoutMiddleware;

class PublisherTest extends TestCaseWithoutMiddleware
{
    /**
     * response of publisher List
     *
     * @return array
     */
    public static function getPublisherListJson()
    {
        return [
            "id",
            "name",
            "host",
            "username",
            "created_at",
            "updated_at"
        ];
    }

    public function getPublisherDetailJson()
    {
        return [
            "id",
            "name",
            "host",
            "username"
        ];
    }

    public function testList()
    {
        $publisher = factory(Publisher::class)->make();
        $publisher->id = 1;
        $publisher->created_at = '2019-09-09 12:37:55';
        $publisher->updated_at = '2019-09-09 12:37:55';
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'all' => [$publisher]
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);

        $response = $this->get('/api/publishers');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [self::getPublisherListJson()]]);
    }

    public function testListNotFoundAnyObjects()
    {
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'all' => []
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);

        $response = $this->get('/api/publishers');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    public function testShow()
    {
        $publisher = factory(Publisher::class)->make();
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $publisher
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);
        $response = $this->get('/api/publishers/1');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => $this->getPublisherDetailJson()
            ]);
    }

    public function testShowNotFound()
    {
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);
        $response = $this->get('/api/publishers/' . 1);
        $response->assertStatus(404);
    }

    public function testFailSave()
    {
        $publisher = factory(Publisher::class)->make();
        $request = [
            'name' => $publisher->name,
            'host' => $publisher->host,
            'username' => $publisher->username,
            'password' => $publisher->password
        ];
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive('save')
            ->withAnyArgs()
            ->once()
            ->andThrow(new \Exception)
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);
        $response = $this->json('POST', '/api/publishers', $request);
        $response->assertStatus(500);
    }

    public function testDestroyNotFound()
    {
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                "find" => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);
        $response = $this->json('DELETE', '/api/publishers/' . 1);
        $response->assertStatus(404);
    }

    public function testFailDestroy()
    {
        $publisher = factory(Publisher::class)->make();
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $publisher,
                'delete' => false
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);
        $response = $this->json('DELETE', '/api/publishers/1');
        $response->assertStatus(500);
    }

    public function testDestroy()
    {
        $publisher = factory(Publisher::class)->make();
        $mock = Mockery::mock(PublisherRepository::class)->makePartial()
            ->shouldReceive([
                "find" => $publisher,
                "delete" => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\PublisherRepository', $mock);
        $response = $this->json('DELETE', '/api/publishers/1');
        $response->assertStatus(200);
    }
}
