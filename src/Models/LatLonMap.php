<?php


namespace Twdd\Models;


use Jenssegers\Mongodb\Eloquent\Model;

class LatLonMap extends Model
{
    public const ReturnFirst = 1;
    public const ReturnAll = 2;
    public const ReturnCount = 3;

    protected $connection = 'mongodb';
    protected $collection = 'latlon_maps';


    protected $guarded = [];

}
