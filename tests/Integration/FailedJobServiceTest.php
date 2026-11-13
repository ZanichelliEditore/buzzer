<?php

namespace Tests\Integration;

use App\Http\Repositories\FailedJobRepository;
use App\Http\Services\FailedJobService;
use App\Models\FailedJob;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCaseWithoutMiddleware;

class FailedJobServiceTest extends TestCaseWithoutMiddleware
{
    use DatabaseTransactions;

    private FailedJobService $service;

    protected function setUp(): void
    {
        parent::setUp();

        FailedJob::query()->delete();
        $this->service = new FailedJobService(new FailedJobRepository());
    }

    private function createFailedJob(int $subscriberId, string $channelName): void
    {
        $this->insertFailedJob([
            'data' => ['command' => 'not-a-serialized-job'],
            'recipient' => ['subscriber_id' => $subscriberId, 'channel_name' => $channelName],
        ]);
    }

    private function insertFailedJob(array $payload): void
    {
        FailedJob::insert([
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode($payload),
            'exception' => 'error',
            'failed_at' => now(),
        ]);
    }

    public function testFailedJobsBySubscriberCountsPerChannel()
    {
        $subscriber = Subscriber::factory()->create(['email' => 'sub@zanichelli.it']);

        $this->createFailedJob($subscriber->id, 'channelA');
        $this->createFailedJob($subscriber->id, 'channelA');
        $this->createFailedJob($subscriber->id, 'channelB');

        $this->assertSame(
            ['channelA' => 2, 'channelB' => 1],
            $this->service->failedJobsBySubscriber()['sub@zanichelli.it']
        );
    }

    public function testFailedJobsBySubscriberGroupsDifferentSubscribersByEmail()
    {
        $first = Subscriber::factory()->create(['email' => 'first@zanichelli.it']);
        $second = Subscriber::factory()->create(['email' => 'second@zanichelli.it']);

        $this->createFailedJob($first->id, 'channelA');
        $this->createFailedJob($second->id, 'channelA');
        $this->createFailedJob($second->id, 'channelA');

        $this->assertSame(
            [
                'first@zanichelli.it' => ['channelA' => 1],
                'second@zanichelli.it' => ['channelA' => 2],
            ],
            $this->service->failedJobsBySubscriber()
        );
    }

    public function testFailedJobsBySubscriberGroupsSubscribersWithoutEmailUnderDefaultRecipients()
    {
        $subscriber = Subscriber::factory()->create(['email' => null]);

        $this->createFailedJob($subscriber->id, 'channelA');

        $this->assertSame(
            [config('mail.email_to') => ['channelA' => 1]],
            $this->service->failedJobsBySubscriber()
        );
    }
}
