<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPublish extends Model
{
    protected $table = 'channel_publish';

    protected $fillable = ['publisher_id', 'channel_id'];

    public function publisher() {
        return $this->hasOne('App\Models\Publisher', 'id', 'publisher_id');
    }

    public function channel() {
        return $this->hasOne('App\Models\Channel', 'id', 'channel_id');
    }
}
