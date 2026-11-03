<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $table = 'subscribers';

    protected $fillable = ['name', 'host'];

    public function subscriber()
    {
        return $this->hasOne('App\Models\Subscriber', 'id', 'subscriber_id');
    }

    public function registrations()
    {
        return $this->hasMany('App\Models\ChannelSubscribe');
    }
}
