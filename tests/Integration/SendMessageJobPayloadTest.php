<?php

namespace Tests\Integration;

use App\Events\SendMessageEvent;
use App\Jobs\SendMessageJob;
use App\Models\ChannelSubscribe;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Queue;
use Tests\TestCaseWithoutMiddleware;

class SendMessageJobPayloadTest extends TestCaseWithoutMiddleware
{
    use DatabaseTransactions;

    /**
     * Build the payload exactly as the queue does when a job is pushed.
     */
    private function createPayload(object $job): array
    {
        $queue = new class extends Queue {
            public function build(object $job): array
            {
                return json_decode($this->createPayload($job, 'default'), true);
            }
        };
        $queue->setContainer($this->app);

        return $queue->build($job);
    }

    public function testSendMessageJobPayloadContainsRecipient()
    {
        $channelSubscribe = ChannelSubscribe::factory()->create();
        $job = new SendMessageJob(
            new SendMessageEvent(new Message('body'), 'host', $channelSubscribe, 'low', 'ordini', 'subscriberName')
        );

        $payload = $this->createPayload($job);

        $this->assertSame(
            ['subscriber_id' => $channelSubscribe->subscriber_id, 'channel_name' => 'ordini'],
            $payload['recipient']
        );
    }

    public function testOtherJobsPayloadHasNoRecipient()
    {
        $payload = $this->createPayload(new OtherQueuedJob());

        $this->assertArrayNotHasKey('recipient', $payload);
    }
}

class OtherQueuedJob implements ShouldQueue
{
    use Queueable;
}
