<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Constants\Authentication;

class CreateChannelSubscribeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_subscribe', function (Blueprint $table) {
            
            $table->bigIncrements('id');
            $table->bigInteger('subscriber_id')->unsigned();
            $table->bigInteger('channel_id')->unsigned();
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->string('endpoint');
            $table->enum('authentication', [Authentication::BASIC, Authentication::OAUTH2, Authentication::NONE]);
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_subscribe');
    }
}
