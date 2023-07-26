<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Models\Channel;
use App\Models\Subscriber;
use Faker\Generator as Faker;
use App\Models\ChannelSubscribe;
use App\Constants\Authentication;

$factory->define(ChannelSubscribe::class, function (Faker $faker) {
    return [
        'subscriber_id' => factory(Subscriber::class)->create()->id,
        'channel_id' => factory(Channel::class)->create()->id,
        'endpoint' => Str::random(10),
        'authentication' => $faker->randomElement([Authentication::BASIC, Authentication::OAUTH2, Authentication::NONE]),
        'username' => 'test',
        'password' => 'test'
    ];
});
