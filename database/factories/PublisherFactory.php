<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Models\Publisher;
use Faker\Generator as Faker;

$factory->define(Publisher::class, function (Faker $faker) {
    return [
        'name' => Str::random(50),
        'host' => "http://" . Str::random(10) . '.zanichelli',
        'username' => Str::random(50),
        'password' => Hash::make('password'), // password
        'created_at' => $faker->dateTime($max = 'now', $timezone = null),
        'updated_at' => $faker->dateTime($max = 'now', $timezone = null),
    ];
});
