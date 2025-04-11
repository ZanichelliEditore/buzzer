<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCaseWithoutMiddleware;
use App\Http\Services\Mailer;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Http\Repositories\FailedJobRepository;


class CommandTooManyFailedJobsTest extends TestCaseWithoutMiddleware
{
    #[DataProvider('tooManyFailedJobsProvider')]
    public function testTooManyFailedJobs($failedJobsCount, $emailSended)
    {
        $this->app->instance(
            'App\Http\Repositories\FailedJobRepository',
            Mockery::mock(FailedJobRepository::class)->makePartial()
                ->shouldReceive([
                    'count' => $failedJobsCount
                ])
                ->once()
                ->getMock()
        );

        $this->app->instance(
            'App\Http\Services\Mailer',
            Mockery::mock(Mailer::class)->makePartial()
                ->shouldReceive('dispatchEmail')
                ->times($emailSended)
                ->getMock()
        );
        $this->artisan('check:failed-jobs')->assertExitCode(0);
    }

    public static function tooManyFailedJobsProvider()
    {
        return [
            [99, 0],
            [101, 1],
        ];
    }
}
