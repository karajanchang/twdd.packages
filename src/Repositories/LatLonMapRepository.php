<?php


namespace Twdd\Repositories;


use Twdd\Models\LatLonMap;
use Zhyu\Repositories\Eloquents\Repository;

class LatLonMapRepository extends Repository
{


    public function model()
    {
        return LatLonMap::class;
    }

    private function getLatlon($lat, $lon){
        $latlon = [
            $lon, $lat
        ];

        return $latlon;
    }

    private function validate(array $params = []){
        $cols = [
            'city', 'city_id', 'district', 'district_id', 'addr', 'zip', 'address'
        ];
        foreach($cols as $col){
            if(!isset($params[$col])){

                return false;
            }
        }

        return true;
    }

    public function nearByLatLon($lat, $lon, float $maxDistance = 50, int $return = 1){
        $qb = $this->where('latlon', 'near', [
            '$geometry' => [
                'type' => 'Point',
                'coordinates' => $this->getLatlon($lat, $lon),
            ],
            '$maxDistance' => $maxDistance,
        ]);
        switch($return){
            case 2:
                $rows = $qb->get();
                break;
            case 3:
                $rows = $qb->count();
                break;
            default:
                $rows = $qb->first();
        }

        return $rows;
    }


    public function insertByParams(array $params = []){
        if($this->validate($params)===false){

            return false;
        }

        if($this->nearByLatLon($params['lat'], $params['lon'], 50, LatLonMap::ReturnCount) > 0){

            return false;
        }

        $this->insert([
            'latlon' => $this->getLatlon($params['lat'], $params['lon']),
            'zip' => (string) $params['zip'],
            'city_id' => (int) $params['city_id'],
            'district_id' => (int) $params['district_id'],
            'city' => $params['city'],
            'district' => $params['district'],
            'addr' => $params['addr'],
            'address' => $params['address'],
        ]);
    }

}