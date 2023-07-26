<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'channels';

    protected $fillable = ['name', 'priority'];

    public function subscribers()
    {
        return $this->belongsToMany('App\Models\Subscriber', 'channel_subscribe');
    }

    public function publishers()
    {
        return $this->belongsToMany('App\Models\Publisher', 'channel_publish');
    }

    public function registrations()
    {
        return $this->hasMany('App\Models\ChannelSubscribe');
    }
}
