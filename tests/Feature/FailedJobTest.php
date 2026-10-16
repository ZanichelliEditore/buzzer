<?php

namespace Tests\Feature;

use Mockery;
use Exception;
use App\Models\Message;
use App\Models\FailedJob;
use App\Jobs\SendMessageJob;
use App\Events\SendMessageEvent;
use App\Models\ChannelSubscribe;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCaseWithoutMiddleware;
use App\Http\Repositories\FailedJobRepository;

class FailedJobTest extends TestCaseWithoutMiddleware
{
    private function getJsonFragment(?FailedJob $failedJob = null, ?ChannelSubscribe $channelSubscribe = null): array
    {
        if (is_null($failedJob)) {
            return [
                "data" => []
            ];
        }

        return [
            "data" => [
                [
                    "exception" => $failedJob->exception,
                    "failed_at" => $failedJob->failed_at,
                    "id" => $failedJob->id,
                    "payload" => $failedJob->payload,
                    "subscriber" => $channelSubscribe ? $channelSubscribe->subscriber->name : '',
                    "channel" => $channelSubscribe ? $channelSubscribe->channel->name : ''
                ]
            ]
        ];
    }

    private function mockFindingFailedJob(int $id): void
    {
        $mock = Mockery::mock(FailedJobRepository::class)->makePartial()
            ->shouldReceive([
                'find' => FailedJob::factory()->make(['id' => $id])
            ])
            ->withAnyArgs()
            ->getMock();
        $this->app->instance('App\Http\Repositories\FailedJobRepository', $mock);
    }

    private function assertSuccessLogged(array $expected): void
    {
        Log::shouldHaveReceived('info')
            ->withArgs(fn($message) => str_starts_with($message, '200* ') && json_decode(substr($message, 5), true) === $expected)
            ->once();
    }

    public function testSuccesfullyListFailedJob()
    {
        $channelSubscribe = ChannelSubscribe::factory()->create();
        $sendMessageJob = new SendMessageJob(new SendMessageEvent(new Message('messaggio'), 'host', $channelSubscribe, 'highpriority', 'channelName', 'publisherName'));

        $failedJob = FailedJob::factory()->make([
            'payload' => json_encode(["data" => ["command" => serialize($sendMessageJob)]]),

        ]);

        $paginator = new Paginator([$failedJob], 12, 1);
        $mock = Mockery::mock(FailedJobRepository::class)->makePartial()
            ->shouldReceive([
                'all' => $paginator
            ])
            ->withAnyArgs()
            ->getMock();
        $this->app->instance('App\Http\Repositories\FailedJobRepository', $mock);
        $response = $this->json('GET', '/api/failedJobs');

        $response->assertStatus(200)->assertJsonFragment($this->getJsonFragment($failedJob, $channelSubscribe));
    }

    public function testListFailedJobInvalidData()
    {
        $paginator = new Paginator([], 12, 1);
        $mock = Mockery::mock(FailedJobRepository::class)->makePartial()
            ->shouldReceive([
                'all' => $paginator
            ])
            ->withAnyArgs()
            ->getMock();
        $this->app->instance('App\Http\Repositories\FailedJobRepository', $mock);

        $response = $this->json('GET', '/api/failedJobs?limit=a');
        $response->assertStatus(422)->assertJsonFragment(["errors" => ["limit" => ["The limit must be an integer."]], "message" => "Data is invalid"]);

        $response = $this->json('GET', '/api/failedJobs?limit=1&order=sacc');
        $response->assertStatus(422)->assertJsonFragment(["errors" => ["order" => ["The selected order is invalid."]], "message" => "Data is invalid"]);

        $response = $this->json('GET', '/api/failedJobs?limit=1&order=asc&orderBy=wee');
        $response->assertStatus(422)->assertJsonFragment(["errors" => ["orderBy" => ["The selected order by is invalid."]], "message" => "Data is invalid"]);
    }

    public function testListNoFailedJob()
    {
        $paginator = new Paginator([], 12, 1);
        $mock = Mockery::mock(FailedJobRepository::class)->makePartial()
            ->shouldReceive([
                'all' => $paginator
            ])
            ->withAnyArgs()
            ->getMock();
        $this->app->instance('App\Http\Repositories\FailedJobRepository', $mock);
        $response = $this->get('/api/failedJobs');
        $response->assertStatus(200)->assertJsonFragment($this->getJsonFragment());
    }

    public function testDestroyUnrealFailedJob()
    {
        $mock = Mockery::mock(FailedJobRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->getMock();
        $this->app->instance('App\Http\Repositories\FailedJobRepository', $mock);
        $response = $this->delete('/api/failedJobs/' . 1);
        $response->assertStatus(404);
    }

    public function testRetryUnrealFailedJob()
    {
        $mock = Mockery::mock(FailedJobRepository::class)->makePartial()
            ->shouldReceive([
                'find' => null
            ])
            ->withAnyArgs()
            ->getMock();
        $this->app->instance('App\Http\Repositories\FailedJobRepository', $mock);
        $response = $this->get('/api/failedJobs/retry/' . 1);
        $response->assertStatus(404);
    }

    public function testShouldRetryFailedJob()
    {
        $this->mockFindingFailedJob(5);
        Artisan::shouldReceive('call')->once()->with('queue:retry', ['id' => 5])->andReturn(0);
        Log::spy();

        $response = $this->get('/api/failedJobs/retry/5');

        $response->assertStatus(Response::HTTP_OK)->assertExactJson(['message' => __('messages.RetrySuccess')]);
        $this->assertSuccessLogged([
            'action' => 'RETRY',
            'object_type' => 'failedJob',
            'object_id' => 5,
            'content' => __('messages.RetrySuccess')
        ]);
    }

    public function testShouldReturn500WhenRetryExitsWithNonZeroCode()
    {
        $this->mockFindingFailedJob(5);
        Artisan::shouldReceive('call')->once()->with('queue:retry', ['id' => 5])->andReturn(1);

        $response = $this->get('/api/failedJobs/retry/5');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)->assertExactJson(['message' => __('messages.RetryError') . '5']);
    }

    public function testShouldReturn500WhenRetryThrows()
    {
        $this->mockFindingFailedJob(5);
        Artisan::shouldReceive('call')->once()->with('queue:retry', ['id' => 5])->andThrow(new Exception('queue down'));

        $response = $this->get('/api/failedJobs/retry/5');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)->assertExactJson(['message' => __('messages.RetryError') . '5']);
    }

    public function testShouldDeleteFailedJob()
    {
        $this->mockFindingFailedJob(5);
        Artisan::shouldReceive('call')->once()->with('queue:forget', ['id' => 5])->andReturn(0);
        Log::spy();

        $response = $this->delete('/api/failedJobs/5');

        $response->assertStatus(Response::HTTP_OK)->assertExactJson(['message' => __('messages.DeleteSuccess')]);
        $this->assertSuccessLogged([
            'action' => 'DELETE',
            'object_type' => 'failedJob',
            'object_id' => 5,
            'content' => __('messages.DeleteSuccess')
        ]);
    }

    public function testShouldReturn500WhenDeleteExitsWithNonZeroCode()
    {
        $this->mockFindingFailedJob(5);
        Artisan::shouldReceive('call')->once()->with('queue:forget', ['id' => 5])->andReturn(1);

        $response = $this->delete('/api/failedJobs/5');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)->assertExactJson(['message' => __('messages.DeleteError') . '5']);
    }

    public function testShouldReturn500WhenDeleteThrows()
    {
        $this->mockFindingFailedJob(5);
        Artisan::shouldReceive('call')->once()->with('queue:forget', ['id' => 5])->andThrow(new Exception('queue down'));

        $response = $this->delete('/api/failedJobs/5');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)->assertExactJson(['message' => __('messages.DeleteError') . '5']);
    }

    public function testShouldFlushAllFailedJobs()
    {
        Artisan::shouldReceive('call')->once()->with('queue:flush')->andReturn(0);

        $response = $this->delete('/api/failedJobs/all');

        $response->assertStatus(Response::HTTP_OK)->assertExactJson(['message' => __('messages.DeleteSuccess')]);
    }

    public function testShouldReturn500WhenFlushExitsWithNonZeroCode()
    {
        Artisan::shouldReceive('call')->once()->with('queue:flush')->andReturn(1);

        $response = $this->delete('/api/failedJobs/all');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)->assertExactJson(['message' => __('messages.DeleteError')]);
    }

    public function testShouldRetryAllFailedJobs()
    {
        Artisan::shouldReceive('call')->once()->with('queue:lazy-retry')->andReturn(0);

        $response = $this->get('/api/failedJobs/retry/all');

        $response->assertStatus(Response::HTTP_OK)->assertExactJson(['message' => __('messages.RetrySuccess')]);
    }

    public function testShouldReturn500WhenRetryAllExitsWithNonZeroCode()
    {
        Artisan::shouldReceive('call')->once()->with('queue:lazy-retry')->andReturn(1);

        $response = $this->get('/api/failedJobs/retry/all');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)->assertExactJson(['message' => __('messages.RetryError') . '1']);
    }
}
