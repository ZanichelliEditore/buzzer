<?php

namespace App\Http\Resources;

use App\Http\Resources\ChannelSimpleResource;
use App\Http\Resources\PublisherSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherChannelResource extends JsonResource
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
            'publisher' => new PublisherSimpleResource($this->publisher),
            'channel' => new ChannelSimpleResource($this->channel)
        ];
    }
}
