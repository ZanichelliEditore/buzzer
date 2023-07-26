<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Publisher extends Authenticatable
{

    protected $table = 'publishers';

    protected $fillable = [
        'name', 'host', 'username', 'password',
    ];

    public function channels()
    {
        return $this->belongsToMany('App\Models\Channel', 'channel_publish');
    }

    public function registrations()
    {
        return $this->hasMany('App\Models\ChannelPublish');
    }
}
