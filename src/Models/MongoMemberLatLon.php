<?php
namespace Twdd\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MongoMemberLatLon extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'member_latlon';

    protected $guarded = [];
}