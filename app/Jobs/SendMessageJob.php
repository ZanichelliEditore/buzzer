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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $event;
    private $body;
    private $startTime;

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
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

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
        $this->timeout = config("queue.timeout");
    }

    /**
     * Execute the job.
     *
     * @param GuzzleService $guzzleService
     * @return void
     */
    public function handle(GuzzleService $guzzleService)
    {
        try {
            Log::withContext([
                "subscriber" => $this->event->subscriberName,
                "channel" => $this->event->channelName,
                "priority" => $this->event->channelSubscribe->channelPriority,
                "authentication" => $this->event->channelSubscribe->authentication,
            ]);

            $pausedKey = Config::get('cache.publisher_paused_key_prefix') . $this->event->channelSubscribe->subscriber_id;
            if (Cache::has($pausedKey)) {
                Log::warning("Message paused");
                $this->fail("Subscriber Paused");
                return;
            }

            $this->startTime = microtime(true);
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
            Log::info("Message sent successfully", [
                "time" => microtime(true) - $this->startTime,
            ]);
        } catch (\Exception $e) {
            Log::error('Message sending failed', [
                "time" => microtime(true) - $this->startTime,
                "attempts" => $this->attempts(),
                "exception" => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getEvent(): SendMessageEvent
    {
        return $this->event;
    }
}
