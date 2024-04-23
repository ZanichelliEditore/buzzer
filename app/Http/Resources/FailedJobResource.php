<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $payload = json_decode($this->payload);
        $channelSubscribe = null;

        if (isset($payload->data->command)) {
            try {
                $channelSubscribe = unserialize($payload->data->command)->getEvent()->channelSubscribe;
            } catch (ModelNotFoundException) {
                Log::warning('[FAILED JOB RESOURCE] Model not found');
            }
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
