<?php

namespace App\Listeners;

use App\Jobs\SendMessageJob;
use App\Events\SendMessageEvent;
use Illuminate\Support\Facades\Config;

class SendMessageListener
{
    /**
     * Handle the event.
     *
     * @param  SendMessageEvent  $event
     * @return void
     */
    public function handle(SendMessageEvent $event)
    {
        SendMessageJob::dispatch($event)->onQueue($this->getPriorityQueueName($event->channelPriority));
    }

    /**
     * @param string $channelPriority
     * @return string
     */
    private function getPriorityQueueName(string $channelPriority) : string
    {
        if(Config::get('queue.default') === 'redis') return '{' . $channelPriority . '}';
        return $channelPriority;
    }
}
