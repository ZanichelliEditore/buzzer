<?php

use App\Models\FailedJob;
use Faker\Generator as Faker;

$factory->define(FailedJob::class, function (Faker $faker) {
    return [
        'connection' => $faker->text(),
        'queue' => $faker->text(),
        'payload' => json_encode(["data" => ["command" => "O:23:\"App\Jobs\SendMessageJob\":2:{s:30:\"\u0000App\Jobs\SendMessageJob\u0000event\";O:27:\"App\Events\SendMessageEvent\":4:{s:7:\"message\";O:18:\"App\Models\Message\":1:{s:24:\"\u0000App\Models\Message\u0000body\";s:18:\"messaggio di prova\";}s:4:\"host\";s:18:\"http:\/\/10.100.0.7\/\";s:16:\"channelSubscribe\";O:45:\"Illuminate\Contracts\Database\ModelIdentifier\":5:{s:5:\"class\";s:27:\"App\Models\ChannelSubscribe\";s:2:\"id\";i:1;s:9:\"relations\";a:0:{}s:10:\"connection\";s:5:\"mysql\";s:15:\"collectionClass\";N;}s:15:\"channelPriority\";s:7:\"default\";}s:5:\"queue\";s:9:\"{default}\";}"]]),
        'exception' => $faker->text(),
        'failed_at' => now()
    ];
});
