<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Models\Publisher;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;

$factory->define(Publisher::class, function (Faker $faker) {
    return [
        'name' => Str::random(50),
        'host' => "http://" . Str::random(10) . '.zanichelli',
        'username' => Str::random(50),
        'password' => Hash::make('password'), // password
        'created_at' => $faker->dateTime(),
        'updated_at' => $faker->dateTime(),
    ];
});
