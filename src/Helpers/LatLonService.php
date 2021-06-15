<?php


namespace Twdd\Helpers;

use ArrayAccess;
use Illuminate\Support\Facades\Log;
use Twdd\Facades\GoogleMap;
use Twdd\Repositories\DistrictRepository;

class LatLonService implements ArrayAccess
{
    private $repository;
    private $array_maps = [];

    protected $attributes = [
        'lat' => null,
        'lon' => null,
        'city' => null,
        'city_id' => null,
        'district' => null,
        'district_id' => null,
        'zip' => null,
    ];

    public function __construct()
    {
        $this->repository = app()->make(DistrictRepository::class);

        return $this;
    }

    public function locationFromZip($zip){

        return $this->repository->locationFromZip($zip);
    }

    public function citydistrictFromCityAndDistrict($cityName, $districtName){
        $odistricts = $this->repository->allIsopen();
        $districts = $odistricts->where('city', $cityName)->where('district', $districtName);
        if(count($districts)==0){
            $zip = $this->zipFromDisk($cityName, $districtName);
            $districts = $odistricts->where('zip', $zip);

            if(count($districts)==0) {

                $all = [
                    'city_id' => 0,
                    'city' => '',
                    'district_id' => 0,
                    'district' => '',
                    'zip' => 0,
                ];
                $this->setAll($all);

                return $this;
            }
        }
        $district = $districts->first();

        $all = [
            'city_id' => $district->city_id,
            'city' => $district->city,
            'district_id' => $district->district_id,
            'district' => $district->district,
            'zip' =>  $district->zip,
        ];
        $this->setAll($all);

        return $this;
    }

    public function getArrayMaps(){
        if(count($this->array_maps)==0) {
            $file = __DIR__ . '/../Models/location.php';
            if (file_exists($file)) {
                $this->array_maps = include_once $file;
            }
        }

        return $this->array_maps;
    }

    public function zipFromDisk(string $cityName, string $districtName){
        $zip = null;
        $citys = $this->getArrayMaps();
        if(is_array($citys)) {
            foreach ($citys as $city => $districts) {
                if ($city == trim($cityName)) {
                    $key = trim($districtName);
                    array_map(function ($ds) use ($key, &$zip) {
                        if (!is_null($zip)) {

                            return null;
                        }
                        if (array_key_exists($key, $ds)) {

                            $zip = $ds[$key];
                        }
                    }, $districts);
                }
            }
        }

        return $zip;
    }



    public function citydistrictFromLatlonOrZip($lat, $lon, $zip = null){
        $all = [
            'city_id' => null,
            'city' => null,
            'district_id' => null,
            'district' => null,
            'zip' => null,
        ];

        $if_in = false;
        if(!empty($zip)) {
            $cityDistricts = $this->locationFromZip($zip);
            if (count($cityDistricts)) {
                $cityDistrict = $cityDistricts->first();
                if (isset($cityDistrict->city_id) && isset($cityDistrict->district_id)) {
                    $if_in = true;
                    $all = [
                        'lat' => $lat,
                        'lon' => $lon,
                        'city_id' => $cityDistrict->city_id,
                        'city' => $cityDistrict->city,
                        'district_id' => $cityDistrict->district_id,
                        'district' => $cityDistrict->district,
                        'zip' => $cityDistrict->zip,
                    ];
                }
            }
        }
        if($if_in===true){
            $this->setAll($all);

            return $this;
        }

        if($lat==0 && $lon==0){
            $this->setAll($all);

            return $this;
        }

        $location = GoogleMap::latlon($lat, $lon);
        if((int)($location->city_id) > 0 && (int) $location->district_id >0 ){
            $all = [
                'lat' => $lat,
                'lon' => $lon,
                'city_id' => $location->city_id,
                'city' => $location->city,
                'district_id' => $location->district_id,
                'district' => $location->district,
                'zip' => $location->zip,
            ];
        }

        $this->setAll($all);

        return $this;
    }

    public function citydistrictFromDistrictIdOrZip(int $district_id = null, string $zip = null){
        $all = [
            'city_id' => null,
            'city' => null,
            'district_id' => null,
            'district' => null,
            'zip' => null,
        ];
        if(!is_null($district_id)){

            $cityDistricts = $this->repository->locationFromDistrictId($district_id);
            if($cityDistricts->count()>0) {
                $cityDistrict = $cityDistricts->first();

                $all = [
                    'lat' => null,
                    'lon' => null,
                    'city_id' => $cityDistrict->city_id,
                    'city' => $cityDistrict->city,
                    'district_id' => $cityDistrict->district_id,
                    'district' => $cityDistrict->district,
                    'zip' => $cityDistrict->zip,
                ];
            }
        }
        if(!is_null($zip)){

            $cityDistricts = $this->repository->locationFromZip($zip);
            if($cityDistricts->count()>0) {
                $cityDistrict = $cityDistricts->first();

                $all = [
                    'lat' => null,
                    'lon' => null,
                    'city_id' => $cityDistrict->city_id,
                    'city' => $cityDistrict->city,
                    'district_id' => $cityDistrict->district_id,
                    'district' => $cityDistrict->district,
                    'zip' => $cityDistrict->zip,
                ];
            }
        }

        $this->setAll($all);

        return $this;
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


    private function setAll(array $all = []){
        foreach($all as $key => $val){
            $this->$key = $val;
        }
    }
}
