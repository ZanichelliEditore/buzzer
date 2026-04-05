<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class SendMessageEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $host;
    public $channelSubscribe;
    public $channelPriority;
    public $channelName;
    public $subscriberName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Message $message, $host, $channelSubscribe, $channelPriority, $channelName, $subscriberName)
    {
        $this->message = $message;
        $this->host = $host;
        $this->channelSubscribe = $channelSubscribe;
        $this->channelPriority = $channelPriority;
        $this->channelName = $channelName;
        $this->subscriberName = $subscriberName;
    }
}
