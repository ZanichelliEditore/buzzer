<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Models\Subscriber;
use Faker\Generator as Faker;

$factory->define(Subscriber::class, function (Faker $faker) {
    return [
        'name' => Str::random(50),
        'host' => "http://" . Str::random(10) . '.zanichelli/',
        'created_at' => $faker->dateTime($max = 'now', $timezone = null),
        'updated_at' => $faker->dateTime($max = 'now', $timezone = null),
    ];
});
