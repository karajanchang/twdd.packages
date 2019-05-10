<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 00:11
 */

namespace Twdd\GoogleMap;


class Address extends GoogleMapAbstract implements GoogleMapInterface
{

    public function __construct($address)
    {
        $this->address = $address;
    }

    public function url(){

        return 'https://maps.googleapis.com/maps/api/geocode/json?language=zh-TW&address='.$this->address.'&key='.env('GOOGLE_API_KEY');
    }



}