<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use App\Http\Repositories\FailedJobRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FailedJobService
{
    private FailedJobRepository $failedJobRepository;

    public function __construct(FailedJobRepository $failedJobRepository)
    {
        $this->failedJobRepository = $failedJobRepository;
    }

    /**
     * Count the failed jobs per subscriber email and channel, reading the failed jobs payload.
     * Failed jobs of subscribers without email are grouped under the DEVELOPER key.
     *
     * @return array<string, array<string, int>> [email => [channel name => failed jobs count]]
     */
    public function failedJobsBySubscriber()
    {
        $counts = [];

        foreach ($this->failedJobRepository->cursor() as $failedJob) {
            $payload = json_decode($failedJob->payload);
            if (!isset($payload->data->command)) {
                continue;
            }

            try {
                $event = unserialize($payload->data->command)->getEvent();
                $email = $event->channelSubscribe->subscriber->email;
            } catch (ModelNotFoundException) {
                Log::warning('[FAILED JOB SERVICE] Model not found for failed job ' . $failedJob->id);
                continue;
            } catch (\Throwable) {
                Log::error('[FAILED JOB SERVICE] error' . $failedJob->id);
                continue;
            }

            $recipient = $email ?: config('mail.email_to');
            $counts[$recipient][$event->channelName] = ($counts[$recipient][$event->channelName] ?? 0) + 1;
        }

        return $counts;
    }
}
