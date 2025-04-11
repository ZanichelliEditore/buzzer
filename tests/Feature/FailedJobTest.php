<?php

namespace Tests\Feature;

use Mockery;
use App\Models\Message;
use App\Models\FailedJob;
use App\Jobs\SendMessageJob;
use App\Events\SendMessageEvent;
use App\Models\ChannelSubscribe;
use Illuminate\Pagination\Paginator;
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

    public function testSuccesfullyListFailedJob()
    {
        $channelSubscribe = factory(ChannelSubscribe::class)->create();
        $sendMessageJob = new SendMessageJob(new SendMessageEvent(new Message('messaggio'), 'host', $channelSubscribe, 'highpriority'));

        $failedJob = factory(FailedJob::class)->make([
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
}
