<?php

namespace Tests\Feature;

use Mockery;
use App\Models\FailedJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use App\Http\Repositories\FailedJobRepository;
use Illuminate\Foundation\Console\QueuedCommand;
use Tests\TestCaseWithoutMiddleware;

class CommandQueueLazyRetryTest extends TestCaseWithoutMiddleware
{
    /**
     * @test
     * @dataProvider lazyRetryJobsProvider
     */
    public function lazyRetryJobsTest($failedJobsCount, $commandCount)
    {
        Queue::fake();
        $failedJobsCollection = new Collection();
        for ($i = 0; $i < $failedJobsCount; $i++) {
            $failedJobsCollection->push(factory(FailedJob::class)->make());
        }

        $this->app->instance(
            'App\Http\Repositories\FailedJobRepository',
            Mockery::mock(FailedJobRepository::class)->makePartial()
                ->shouldReceive([
                    'all' => $failedJobsCollection
                ])
                ->withAnyArgs()
                ->once()
                ->getMock()
        );

        $this->artisan('queue:lazy-retry')->assertExitCode(0);
        Queue::assertPushed(QueuedCommand::class, $commandCount);
    }

    static function lazyRetryJobsProvider()
    {
        return [
            [0, 0],
            [3, 1],
            [100, 2],
            [101, 3],
        ];
    }
}
