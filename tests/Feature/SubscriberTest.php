<?php

namespace Tests\Feature;

use Mockery;
use App\Models\Subscriber;
use App\Http\Repositories\SubscriberRepository;
use Tests\TestCaseWithoutMiddleware;

class SubscriberTest extends TestCaseWithoutMiddleware
{
    /**
     * response of subscriber List
     *
     * @return array
     */
    public static function getSubscriberListJson()
    {
        return [
            "id",
            "name",
            "host",
            "created_at",
            "updated_at"
        ];
    }

    public function getSubscriberDetailJson()
    {
        return [
            "id",
            "name",
            "host"
        ];
    }

    /**
     * @test
     * @return void
     */
    public function listTest()
    {
        $subscriber = factory(Subscriber::class)->make();
        $subscriber->id = 1;
        $subscriber->created_at = '2019-09-09 12:37:55';
        $subscriber->updated_at = '2019-09-09 12:37:55';
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                'all' => [$subscriber]
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);

        $response = $this->get('/api/subscribers');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [self::getSubscriberListJson()]]);
    }

    /**
     * @test
     * @return void
     */
    public function listNotFoundAnyObjects()
    {
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                'all' => []
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);

        $response = $this->get('/api/subscribers');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function showTest()
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
        $response = $this->get('/api/subscribers/1');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => $this->getSubscriberDetailJson()
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function showNotFoundTest()
    {
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $response = $this->get('/api/subscribers/' . 1);
        $response->assertStatus(404);
    }

    /**
     * @test
     * @return void
     */
    public function failSaveTest()
    {
        $subscriber = factory(Subscriber::class)->make();
        $request = [
            'name' => $subscriber->name,
            'host' => $subscriber->host
        ];
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive('save')
            ->withAnyArgs()
            ->once()
            ->andThrow(new \Exception)
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $response = $this->json('POST', '/api/subscribers', $request);
        $response->assertStatus(500);
    }

    /**
     * @test
     * @return void
     */
    public function destroyNotFound()
    {
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                "find" => null
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $response = $this->json('DELETE', '/api/subscribers/' . 1);
        $response->assertStatus(404);
    }

    /**
     * @test
     * @return void
     */
    public function failDestroyTest()
    {
        $subscriber = factory(Subscriber::class)->make();
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([
                'find' => $subscriber,
                'delete' => false
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $response = $this->json('DELETE', '/api/subscribers/1');
        $response->assertStatus(500);
    }

    /**
     * @test
     * @return void
     */
    public function destroyTest()
    {
        $subscriber = factory(Subscriber::class)->make();
        $mock = Mockery::mock(SubscriberRepository::class)->makePartial()
            ->shouldReceive([

                "find" => $subscriber,
                "delete" => true
            ])
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance('App\Http\Repositories\SubscriberRepository', $mock);
        $response = $this->json('DELETE', '/api/subscribers/1');
        $response->assertStatus(200);
    }
}
