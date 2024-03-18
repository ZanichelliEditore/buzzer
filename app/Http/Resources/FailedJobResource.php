<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FailedJobResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $channelSubscribe = null;
        $payload = json_decode($this->payload);

        if (isset($payload->data->command)) {
            $channelSubscribe = unserialize($payload->data->command)->getEvent()->channelSubscribe;
        }

        return [
            'id' => $this->id,
            'payload' => $this->payload,
            'exception' => $this->exception,
            'failed_at' => $this->failed_at,
            'subscriber' => $channelSubscribe ? $channelSubscribe->subscriber->name : '',
            'channel' => $channelSubscribe ? $channelSubscribe->channel->name : ''
        ];
    }
}
