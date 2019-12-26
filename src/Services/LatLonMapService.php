<?php


namespace Twdd\Services;


use Twdd\Errors\TaskErrors;
use Twdd\Models\LatLonMap;
use Twdd\Repositories\LatLonMapRepository;
use Twdd\Traits\AttributesArrayTrait;

class LatLonMapService extends ServiceAbstract implements \ArrayAccess
{
    use AttributesArrayTrait;



    public function __construct(LatLonMapRepository $repository, TaskErrors $error)
    {
        $this->repository = $repository;
        $this->error = $error;

        $this->attributes = [
            'lat' => null,
            'lon' => null,
            'city' => null,
            'city_id' => null,
            'district' => null,
            'district_id' => null,
            'zip' => null,
            'address' => null,
            'addr' => null,
        ];
    }

    public function find($lat, $lon){
        if($lat==0 && $lon==0){

            return $this->error->_('4000');
        }

        $row = $this->repository->findByLatLon($lat, $lon);

        if(strlen($row->zip)){
            $this->setAll($row);
        }

        return $this;
    }

    public function near($lat, $lon, int $maxDistance = 50, int $return = 1){
        $row = $this->repository->nearByLatLon($lat, $lon, $maxDistance, $return);

        if(strlen($row->zip)){
            $this->setAll($row);
        }

        return $this;
    }


    public function insert($params){

        $res = $this->repository->insertByParams($params);

        return $res;
    }

    private function setAll(LatLonMap $row){
        $this->lat = $row->lat;
        $this->lon = $row->lon;
        $this->zip = $row->zip;
        $this->city = $row->city;
        $this->city_id = $row->city_id;
        $this->district = $row->district;
        $this->district_id = $row->district_id;
        $this->addr = $row->addr;
        $this->address = $row->address;
    }
}