<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 00:17
 */

namespace Twdd\GoogleMap;

use ArrayAccess;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Zhyu\Facades\ZhyuCurl;


class GoogleMapAbstract implements ArrayAccess
{
    protected $attributes = [];

    const location_maps = [
        'country' => 'country',
        'administrative_area_level_1' => 'city',
        'administrative_area_level_3' => 'district',
        'postal_code' => 'zip',
        'route' => 'route',
        'street_number' => 'street_number',
    ];

    public function fire(){
        try {
            $url = $this->url();
            //dump($url);
            $content = ZhyuCurl::url($url)->get();

            $data = json_decode($content);

            $this->locationFromLatLon($data);

            return $data;
        }catch (Exception $e){
            Log::alert(__CLASS__.'執行錯誤:'.$e->getMessage());
        }
    }

    public function locationFromLatLon($data){
        if(isset($data->results[0]->address_components)){
            $address_components = $data->results[0]->address_components;
            foreach ($address_components as $item){
                if(isset($item->types[0])) {
                    $type = $item->types[0];
                    if(isset(self::location_maps[$type])) {
                        $key = self::location_maps[$type];
                        $this->$key = $item->short_name;
                    }
                }
            }
        }
        if(isset($data->results[0]->geometry->location->lat)){
            $this->offsetSet('lat', $data->results[0]->geometry->location->lat);
            $this->offsetSet('lon', $data->results[0]->geometry->location->lng);
        }

        if(isset($data->results[0]->formatted_address)) {
            $this->address = $data->results[0]->formatted_address;
        }
    }

    public function toArray(){
        return $this->attributes;
    }

    public function offsetExists($offset){

        return isset($this->attributes[$offset]);
    }
    public function offsetGet($offset){
        if(isset($this->attributes[$offset])) {

            return $this->attributes[$offset];
        }
    }
    public function offsetSet($offset, $value){
        $this->attributes[$offset] = $value;
    }
    public function offsetUnset($offset){
        if(isset($this->attributes[$offset])) {
            unset($this->attributes[$offset]);
        }
    }


    public function __set($key, $val){
        $this->offsetSet($key, $val);
    }

    public function __get($key){
        return $this->offsetGet($key);
    }

}
