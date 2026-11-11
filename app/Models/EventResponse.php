<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventResponse extends Model
{
    protected $fillable = ['group_event_id', 'user_id', 'status'];
}
