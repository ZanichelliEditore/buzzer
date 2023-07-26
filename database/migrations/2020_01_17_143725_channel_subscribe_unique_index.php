<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChannelSubscribeUniqueIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channel_subscribe', function (Blueprint $table) {
            $table->unique(['endpoint', 'subscriber_id', 'channel_id'], 'channel_subscribe_subscription_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_subscribe', function (Blueprint $table) {
            $table->dropUnique('channel_subscribe_subscription_unique');
        });
    }
}
