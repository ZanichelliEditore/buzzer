<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Models\Channel;
use App\Models\Publisher;
use Faker\Generator as Faker;
use App\Models\ChannelPublish;

$factory->define(ChannelPublish::class, function (Faker $faker) {
    return [
        'channel_id' => factory(Channel::class)->create()->id,
        'publisher_id' => factory(Publisher::class)->create()->id
    ];
});
