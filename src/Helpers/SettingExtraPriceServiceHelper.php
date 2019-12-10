<?php

namespace Twdd\Helpers;


use Twdd\Facades\LatLonService;
use Twdd\Repositories\SettingExtraPriceRepository;

class SettingExtraPriceServiceHelper
{
    /**
     * @var SettingExtraPriceRepository
     */
    private $repository;

    public function __construct(SettingExtraPriceRepository $repository)
    {

        $this->repository = $repository;
    }

    public function getByCity(int $city_id){

        return $this->repository->allOpen($city_id);
    }

    public function getByLatLonOrZip($lat, $lon, $zip = null){
        $location = LatLonService::citydistrictFromLatlonOrZip($lat, $lon, $zip);

        if(isset($location['city_id'])){

            return $this->getByCity($location['city_id']);
        }
    }
}