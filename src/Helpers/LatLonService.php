<?php


namespace Twdd\Helpers;

use Twdd\Facades\GoogleMap;
use Twdd\Repositories\DistrictRepository;

class LatLonService
{
    private $repository;

    public function __construct()
    {
        $this->repository = app()->make(DistrictRepository::class);

        return $this;
    }

    public function citydistrictFromZip($zip){

        return $this->repository->citydistrictFromZip($zip);
    }

    public function citydistrictFromLatlonOrZip($lat, $lon, $zip = null){
        $all = [
            'city_id' => null,
            'city' => null,
            'district_id' => null,
            'district' => null,
            'zip' => null,
        ];
        if(isset($zip) && strlen($zip)>0){
            $cityDistricts = $this->citydistrictFromZip($zip);
            if (count($cityDistricts)) {
                $cityDistrict = $cityDistricts->first();
                if (isset($cityDistrict->city_id) && isset($cityDistrict->district_id)) {
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
        }else{
            if($lat==0 && $lon==0){

                return $all;
            }
            $location = GoogleMap::latlon($lat, $lon);
            if(isset($location->city_id) && isset($location->district_id)) {
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
        }

        return $all;
    }


}
