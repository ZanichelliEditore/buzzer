<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Events\SendMessageEvent;
use App\Constants\Authentication;
use App\Http\Services\GuzzleService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;


class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $event;
    private $body;


    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 10;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     * @param SendMessageEvent $event
     * @return void
     */
    public function __construct(SendMessageEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param GuzzleService $guzzleService
     * @return void
     */
    public function handle(GuzzleService $guzzleService)
    {
        switch ($this->event->channelSubscribe->authentication) {
            case Authentication::BASIC:
                $guzzleService->sendWithBasicAuth($this->event);
                break;
            case Authentication::NONE:
                $guzzleService->sendWithoutAuth($this->event);
                break;
            case Authentication::OAUTH2:
                $guzzleService->sendWithOAuth2($this->event);
                break;
        }
    }

    public function getEvent(): SendMessageEvent
    {
        return $this->event;
    }
}
