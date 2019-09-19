<?php
namespace Twdd\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MongoDriverLatLon extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'driver_latlon';

    protected $guarded = [];
}