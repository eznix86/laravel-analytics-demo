<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'stores';

    protected $primaryKey = 'store_id';

    public $timestamps = false;

    protected $guarded = [];
}
