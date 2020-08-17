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
use Twdd\Facades\LatLonService;
use Twdd\Models\LatLonMap;
use Twdd\Services\LatLonMapService;
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
            if($this->lookFromLatLonMap()===true){

                return $this;
            }

            $url = $this->url();
            $content = ZhyuCurl::url($url)->get();
            $data = json_decode($content);

            $this->locationFromLatLon($data);

            $this->toArray();

            $this->insert2LatLonMap();


            return $this;
        }catch (Exception $e){
            Log::alert(__CLASS__.'執行錯誤:'.$e->getMessage(), [$this->attributes, $e]);
        }
    }

    //---從LatLonMap去抓對應
    private function lookFromLatLonMap(){
        if(is_float($this->lat)===true && is_float($this->lon)===true ){
            try {
                $location = app(LatLonMapService::class)->near($this->lat, $this->lon, 50, LatLonMap::ReturnFirst);
                if (strlen($location['zip']) > 0 && intval($location['city_id']) > 0 && intval($location['district_id']) > 0) {
                    $this->zip = $location['zip'];
                    $this->city = $location['city'];
                    $this->city_id = $location['city_id'];
                    $this->district = $location['district'];
                    $this->district_id = $location['district_id'];
                    $this->addr = $location['addr'];
                    $this->address = $location['address'];
                    Log::info('GoogleMap使用了LatLonMap，查到了：', [
                        'zip' => $location['zip'],
                        'city' => $location['city'],
                        'city_id' => $location['city_id'],
                        'district' => $location['district'],
                        'district_id' => $location['district_id'],
                        'addr' => $location['addr'],
                        'address' => $location['address'],
                    ]);

                    return true;
                }
            }catch (\Exception $e){
                Log::info(__CLASS__.'::'.__METHOD__.' mongo 查詢失敗', [$e]);
            }
        }

        return false;
    }

    //---塞入LatLonMap
    private function insert2LatLonMap(){
        if(strlen($this->zip) > 0 && intval($this->city_id) > 0 && intval($this->district_id) > 0){
            try {
                app(LatLonMapService::class)->insert($this->attributes);

                Log::info('GoogleMap使用了LatLonMap，加入成功：', $this->attributes);
            }catch(\Exception $e){
                Log::error('GoogleMap使用了LatLonMap，加入失敗：', [$e]);
            }
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

        if(
            (empty($this->lat) || empty($this->lon))
            &&
            isset($data->results[0]->geometry->location->lat) && isset($data->results[0]->geometry->location->lng)
        ){
            $this->offsetSet('lat', $data->results[0]->geometry->location->lat);
            $this->offsetSet('lon', $data->results[0]->geometry->location->lng);
        }

        if(isset($data->results[0]->formatted_address)) {
            $this->address = $data->results[0]->formatted_address;
        }
        $this->paraseLocationFromAddress();

        $this->serial++;

        if($this->serial<5 && (strlen($this->zip)==0 || strlen($this->addr)==0)){
            $this->locationFromLatLon($data);
        }
        $addr = $this->attributes['route'].$this->attributes['street_number'];
        $this->attributes['addr'] = strlen($this->attributes['street_number'])>0 ? $addr.'號' : $addr;
    }

    public function toArray(){
        $cityDistricts = LatLonService::locationFromZip($this->zip);
        if(count($cityDistricts)){
            $cityDistrict = $cityDistricts->first();

            if ((int)($cityDistrict->city_id) > 0 && (int) ($cityDistrict->district_id) > 0) {
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
