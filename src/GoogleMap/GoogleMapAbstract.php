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
use Twdd\Facades\LatLonService;
use Zhyu\Facades\ZhyuCurl;


class GoogleMapAbstract implements ArrayAccess
{
    protected $attributes = [
        'lat' => null,
        'lon' => null,
        'country' => null,
        'city' => null,
        'city_id' => null,
        'district' => null,
        'district_id' => null,
        'zip' => null,
        'address' => null,
        'addr' => null,
        'route' => null,
        'street_number' => null,
    ];
    private $serial = 0;

    const location_maps = [
        'country' => 'country',
        'administrative_area_level_1' => 'city',
        'administrative_area_level_2' => 'city',
        'administrative_area_level_3' => 'district',
        'postal_code' => 'zip',
        'route' => 'route',
        'street_number' => 'street_number',
    ];

    public function fire(){
        try {
            $url = $this->url();
            $content = ZhyuCurl::url($url)->get();
            $data = json_decode($content);
            $this->locationFromLatLon($data);

            return $data;
        }catch (Exception $e){
            Log::alert(__CLASS__.'執行錯誤:'.$e->getMessage());
        }
    }

    private function paraseLocationFromAddress(){
        if(strlen($this->city) > 0 && strlen($this->district) > 0 ) {
            $location = app(\Twdd\Helpers\LatLonService::class)->citydistrictFromCityAndDistrict($this->city, $this->district);
            if (isset($location['city_id'])) {
                $this->city_id = $location['city_id'];
            }
            if (isset($location['district_id'])) {
                $this->district_id = $location['district_id'];
            }
            if (isset($location['zip'])) {
                $this->zip = $location['zip'];
            }
        }

    }

    private function locationFromLatLon($data){
        if(isset($data->results[$this->serial]->address_components)){
            $address_components = $data->results[$this->serial]->address_components;
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
        $this->paraseLocationFromAddress();

        $this->serial++;

        if($this->serial<5 && strlen($this->zip)==0){
            $this->locationFromLatLon($data);
        }
        $addr = $this->attributes['route'].$this->attributes['street_number'];
        $this->attributes['addr'] = strlen($this->attributes['street_number'])>0 ? $addr.'號' : $addr;
    }

    public function toArray(){
        $cityDistricts = LatLonService::locationFromZip($this->zip);
        if(count($cityDistricts)){
            $cityDistrict = $cityDistricts->first();

            if (isset($cityDistrict->city_id) && isset($cityDistrict->district_id)) {
                $this->offsetSet('city_id', $cityDistrict->city_id);
                $this->offsetSet('district_id', $cityDistrict->district_id);
            }

            if (!$this->offsetGet('city') && $cityDistrict->city) {
                $this->offsetSet('city', $cityDistrict->city);
            }

            if (!$this->offsetGet('district') && $cityDistrict->district) {
                $this->offsetSet('', $cityDistrict->district);
            }
        }
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
