<?php


namespace Twdd\Repositories;


use Carbon\Carbon;
use Twdd\Facades\GoogleMap;
use Twdd\Facades\LatLonService;
use Twdd\Models\DriverLocation;
use Zhyu\Repositories\Eloquents\Repository;

class DriverLocationRepository extends Repository
{
    public function model(){

        return DriverLocation::class;
    }

    public function createOrUpdateByDriverId($driver_id, array $params){
        $all = [];
        $all['DriverLat'] = null;
        $all['DriverLon'] = null;
        $all['updatetime'] = Carbon::now()->toDateTimeString();
        $all['gps_ts'] = isset($params['gps_ts']) ? $params['gps_ts'] : null;

        //--處理DriverLat DriverLon city_id district_id
        if(isset($params['lat']) && isset($params['lon'])) {
            if ($params['lat'] != 0 && $params['lon'] != 0) {
                $all['DriverLat'] = $params['lat'];
                $all['DriverLon'] = $params['lon'];
            }

            if($params['city_id']){
                $all['city_id'] = $params['city_id'];
            }
            if($params['district_id']){
                $all['district_id'] = $params['district_id'];
            }
            /*
            $cityDistrict = $this->getCityIdDistrictIdFromParams($params);
            $all['city_id'] = $cityDistrict['city_id'];
            $all['district_id'] = $cityDistrict['district_id'];
            */
        }

        $this->updateOrCreate([
            'driver_id' => $driver_id,
        ],
            $all
        );

        return $all;
    }

    /*
    private function getCityIdDistrictIdFromParams(array $params){
        $all = [
            'city_id' => null,
            'district_id' => null,
        ];
        $zip = isset($params['zip']) ? trim($params['zip']) : null;
        if(strlen($zip)==0){
            if(!is_null($params['lat']) && !is_null($params['lon'])) {
                $location = GoogleMap::latlon($params['lat'], $params['lon']);
                if(isset($location->city_id) && isset($location->district_id)) {

                    $all = [
                        'city_id' => $location->city_id,
                        'district_id' => $location->district_id,
                    ];
                }
            }
        }else {
            $cityDistricts = LatLonService::citydistrictFromZip($zip);
            if (count($cityDistricts)) {
                $cityDistrict = $cityDistricts->first();
                if (isset($cityDistrict->city_id) && isset($cityDistrict->district_id)) {

                    $all = [
                        'city_id' => $cityDistrict->city_id,
                        'district_id' => $cityDistrict->district_id,
                    ];

                }
            }
        }

        return $all;
    }
    */
}