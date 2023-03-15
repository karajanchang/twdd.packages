<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Address extends Model {

    protected $table = 'address';

    protected $guarded = ['id'];

    protected $hidden = ['latlon'];

    public function newQuery()
    {
        return parent::newQuery()->addSelect('*', DB::raw('ST_X(latlon) AS lat'), DB::raw('ST_Y(latlon) AS lon') );
    }
}
