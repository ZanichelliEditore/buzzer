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

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Message $message, $host, $channelSubscribe, $channelPriority)
    {
        $this->message = $message;
        $this->host = $host;
        $this->channelSubscribe = $channelSubscribe;
        $this->channelPriority = $channelPriority;
    }
}
