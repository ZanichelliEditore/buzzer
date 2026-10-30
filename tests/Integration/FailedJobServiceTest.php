<?php

namespace Tests\Integration;

use App\Events\SendMessageEvent;
use App\Http\Repositories\FailedJobRepository;
use App\Http\Services\FailedJobService;
use App\Jobs\SendMessageJob;
use App\Models\Channel;
use App\Models\ChannelSubscribe;
use App\Models\FailedJob;
use App\Models\Message;
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
        // Inside the test transaction: rolled back, local failed jobs are preserved
        FailedJob::query()->delete();
        $this->service = new FailedJobService(new FailedJobRepository());
    }

    private function createFailedJob(ChannelSubscribe $channelSubscribe, Channel $channel, Subscriber $subscriber): void
    {
        $event = new SendMessageEvent(new Message('body'), $subscriber->host, $channelSubscribe, 1, $channel->name, $subscriber->name);
        $payload = json_encode(['data' => ['command' => serialize(new SendMessageJob($event))]]);

        FailedJob::insert([
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => $payload,
            'exception' => 'error',
            'failed_at' => now(),
        ]);
    }

    public function testFailedJobsBySubscriberCountsPerChannel()
    {
        $channelA = factory(Channel::class)->create();
        $channelB = factory(Channel::class)->create();
        $subscriber = factory(Subscriber::class)->create(['email' => 'sub@zanichelli.it']);
        $subscribeA = factory(ChannelSubscribe::class)->create(['subscriber_id' => $subscriber->id, 'channel_id' => $channelA->id]);
        $subscribeB = factory(ChannelSubscribe::class)->create(['subscriber_id' => $subscriber->id, 'channel_id' => $channelB->id]);

        $this->createFailedJob($subscribeA, $channelA, $subscriber);
        $this->createFailedJob($subscribeA, $channelA, $subscriber);
        $this->createFailedJob($subscribeB, $channelB, $subscriber);

        $result = $this->service->failedJobsBySubscriber();

        $this->assertSame(
            [$channelA->name => 2, $channelB->name => 1],
            $result['sub@zanichelli.it']
        );
    }

    public function testFailedJobsBySubscriberGroupsSubscribersWithoutEmailUnderDefaultRecipients()
    {
        $channel = factory(Channel::class)->create();
        $subscriber = factory(Subscriber::class)->create(['email' => null]);
        $subscribe = factory(ChannelSubscribe::class)->create(['subscriber_id' => $subscriber->id, 'channel_id' => $channel->id]);

        $this->createFailedJob($subscribe, $channel, $subscriber);

        $this->assertSame(
            [env('EMAIL_TO') => [$channel->name => 1]],
            $this->service->failedJobsBySubscriber()
        );
    }

    public function testFailedJobsBySubscriberSkipsJobsWithoutCommand()
    {
        FailedJob::insert([
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['data' => []]),
            'exception' => 'error',
            'failed_at' => now(),
        ]);

        $this->assertSame([], $this->service->failedJobsBySubscriber());
    }
}
