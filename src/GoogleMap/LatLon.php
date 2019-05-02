<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 00:11
 */

namespace Twdd\GoogleMap;


class LatLon extends GoogleMapAbstract implements GoogleMapInterface
{

    public function __construct($lat, $lon)
    {
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public function url(){

        return 'https://maps.googleapis.com/maps/api/geocode/json?language=zh-TW&latlng='.$this->lat.','.$this->lon.'&key='.env('GOOGLE_API_KEY');
    }



}