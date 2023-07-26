<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelSubscribe extends Model
{
    protected $table = 'channel_subscribe';

    protected $fillable = ['subscriber_id', 'channel_id', 'endpoint', 'authentication', 'username', 'password'];

    public function subscriber()
    {
        return $this->hasOne('App\Models\Subscriber', 'id', 'subscriber_id');
    }
    public function channel()
    {
        return $this->hasOne('App\Models\Channel', 'id', 'channel_id');
    }
}