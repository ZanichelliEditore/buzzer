<?php

namespace App\Http\Resources;

use App\Http\Resources\ChannelSimpleResource;
use App\Http\Resources\SubscriberSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'endpoint' => $this->endpoint,
            'username' => $this->username,
            'authentication' => $this->authentication,
            'subscriber' => new SubscriberSimpleResource($this->subscriber),
            'channel' => new ChannelSimpleResource($this->channel)
        ];
    }
}
