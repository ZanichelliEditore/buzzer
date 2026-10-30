<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCaseWithoutMiddleware;
use App\Http\Services\Mailer;
use App\Http\Services\FailedJobService;
use App\Http\Repositories\FailedJobRepository;


class CommandTooManyFailedJobsTest extends TestCaseWithoutMiddleware
{
    private function mockRepository(array $failedJobsBySubscriber, int $count = 100)
    {
        $this->mockCount($count);

        $this->app->instance(
            'App\Http\Services\FailedJobService',
            Mockery::mock(FailedJobService::class)
                ->shouldReceive('failedJobsBySubscriber')
                ->once()
                ->andReturn($failedJobsBySubscriber)
                ->getMock()
        );
    }

    private function mockCount(int $count)
    {
        $this->app->instance(
            'App\Http\Repositories\FailedJobRepository',
            Mockery::mock(FailedJobRepository::class)->makePartial()
                ->shouldReceive('count')
                ->once()
                ->andReturn($count)
                ->getMock()
        );
    }

    public function testSendsNothingBelowThreshold()
    {
        $this->mockCount(99);

        $this->app->instance(
            'App\Http\Services\FailedJobService',
            Mockery::mock(FailedJobService::class)
                ->shouldReceive('failedJobsBySubscriber')
                ->never()
                ->getMock()
        );

        $this->app->instance(
            'App\Http\Services\Mailer',
            Mockery::mock(Mailer::class)->makePartial()
                ->shouldReceive('dispatchEmail')
                ->never()
                ->getMock()
        );

        $this->artisan('check:failed-jobs')->assertExitCode(0);
    }

    public function testSendsOneEmailPerSubscriberWithCountPerChannel()
    {
        $this->mockRepository([
            'a@zanichelli.it' => ['Bob' => 2, 'Alice' => 1],
            'b@zanichelli.it' => ['Bob' => 5],
        ]);

        $bodies = [];
        $this->app->instance(
            'App\Http\Services\Mailer',
            Mockery::mock(Mailer::class)->makePartial()
                ->shouldReceive('dispatchEmail')
                ->twice()
                ->andReturnUsing(function ($body, $to) use (&$bodies) {
                    $bodies[$to[0]] = $body;
                })
                ->getMock()
        );

        $this->artisan('check:failed-jobs')->assertExitCode(0);

        $this->assertEqualsCanonicalizing(['a@zanichelli.it', 'b@zanichelli.it'], array_keys($bodies));
        $this->assertStringContainsString('Bob', $bodies['a@zanichelli.it']);
        $this->assertStringContainsString('Alice', $bodies['a@zanichelli.it']);
        $this->assertStringContainsString('2', $bodies['a@zanichelli.it']);
        $this->assertStringNotContainsString('Alice', $bodies['b@zanichelli.it']);
        $this->assertStringContainsString('5', $bodies['b@zanichelli.it']);
    }

    public function testSplitsCommaSeparatedRecipients()
    {
        $this->mockRepository([
            'test@zanichelli.it,test2@zanichelli.it' => ['Bob' => 3],
        ]);

        $this->app->instance(
            'App\Http\Services\Mailer',
            Mockery::mock(Mailer::class)->makePartial()
                ->shouldReceive('dispatchEmail')
                ->once()
                ->with(
                    Mockery::on(fn ($body) => str_contains($body, 'Bob') && str_contains($body, '3')),
                    ['test@zanichelli.it', 'test2@zanichelli.it']
                )
                ->getMock()
        );

        $this->artisan('check:failed-jobs')->assertExitCode(0);
    }

    public function testSendsNothingWhenNoFailedJobs()
    {
        $this->mockRepository([]);

        $this->app->instance(
            'App\Http\Services\Mailer',
            Mockery::mock(Mailer::class)->makePartial()
                ->shouldReceive('dispatchEmail')
                ->never()
                ->getMock()
        );

        $this->artisan('check:failed-jobs')->assertExitCode(0);
    }
}
