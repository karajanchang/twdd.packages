<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 16:23
 */

namespace Twdd\Helpers;

use Twdd\GoogleMap\LatLon;

class GoogleMap
{
    public function latlon($lat, $lon){
        $location = app()->make(LatLon::class, ['lat' => $lat, 'lon' => $lon]);
        $location->fire();

        return $location;
    }

}