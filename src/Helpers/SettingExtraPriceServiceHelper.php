<?php

namespace Twdd\Helpers;


use Twdd\Facades\LatLonService;
use Twdd\Facades\TwddCache;
use Twdd\Services\SettingExtraPriceService;

class SettingExtraPriceServiceHelper
{
    /**
     * @var SettingExtraPriceService
     */
    private $service;

    public function __construct(SettingExtraPriceService $service)
    {
        $this->service = $service;
    }

    public function getByLatLonOrZip($lat, $lon, $zip = null){
        $location = LatLonService::citydistrictFromLatlonOrZip($lat, $lon, $zip);

        if(isset($location['city_id'])){

            return $this->service->getByCity($location['city_id']);
        }

        return $location;
    }

    public function clearCache(){

        return TwddCache::key('SettingExtraPriceAll')->forget();
    }
}