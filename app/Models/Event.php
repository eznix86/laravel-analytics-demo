<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $connection = 'warehouse';

    public $timestamps = false;

    protected $guarded = [];
}
