<?php

namespace Database\Factories;

use App\Constants\Authentication;
use App\Models\Channel;
use App\Models\ChannelSubscribe;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChannelSubscribe>
 */
class ChannelSubscribeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscriber_id' => Subscriber::factory(),
            'channel_id' => Channel::factory(),
            'endpoint' => Str::random(10),
            'authentication' => $this->faker->randomElement([Authentication::BASIC, Authentication::OAUTH2, Authentication::NONE]),
            'username' => 'test',
            'password' => 'test',
        ];
    }
}
