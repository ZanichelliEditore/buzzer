<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\ChannelPublish;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelPublish>
 */
class ChannelPublishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'publisher_id' => Publisher::factory(),
        ];
    }
}
