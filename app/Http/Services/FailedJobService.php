<?php

namespace App\Http\Services;

use App\Jobs\SendMessageJob;
use App\Models\Subscriber;
use App\Http\Repositories\FailedJobRepository;

class FailedJobService
{
    private FailedJobRepository $failedJobRepository;

    public function __construct(FailedJobRepository $failedJobRepository)
    {
        $this->failedJobRepository = $failedJobRepository;
    }

    /**
     * Count the failed jobs per subscriber email and channel, reading the recipient added to the job payload.
     * Failed jobs of subscribers without email (or no longer existing) are grouped under the default email.
     *
     * @return array<string, array<string, int>> [email => [channel name => failed jobs count]]
     */
    public function failedJobsBySubscriber()
    {
        $countsBySubscriber = [];

        foreach ($this->failedJobRepository->cursor() as $failedJob) {
            $recipient = json_decode($failedJob->payload)->{SendMessageJob::RECIPIENT_PAYLOAD_KEY} ?? null;
            if (!isset($recipient->subscriber_id, $recipient->channel_name)) {
                continue;
            }

            $countsBySubscriber[$recipient->subscriber_id][$recipient->channel_name] =
                ($countsBySubscriber[$recipient->subscriber_id][$recipient->channel_name] ?? 0) + 1;
        }

        if (!$countsBySubscriber) return [];

        $emails = Subscriber::whereIn('id', array_keys($countsBySubscriber))->pluck('email', 'id');

        $counts = [];
        foreach ($countsBySubscriber as $subscriberId => $channels) {
            $email = !empty($emails[$subscriberId]) ? $emails[$subscriberId] : config('mail.email_to');
            foreach ($channels as $channelName => $count) {
                $counts[$email][$channelName] = ($counts[$email][$channelName] ?? 0) + $count;
            }
        }

        return $counts;
    }
}
