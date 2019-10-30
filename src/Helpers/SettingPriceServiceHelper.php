<?php


namespace Twdd\Helpers;


use Illuminate\Support\Collection;
use Twdd\Services\Price\SettingLongtermPriceService;
use Twdd\Services\Price\SettingServicePriceService;

class SettingPriceServiceHelper
{
    private $app;

    public function common(int $city_id = 1){
        $app = app(SettingServicePriceService::class);

        return $app->all($city_id);
    }

    public function longterm(int $city_id = 1){
        $app = app(SettingLongtermPriceService::class);

        return $app->all($city_id);
    }

    //---以下的
    public function callType(int $call_type){
        $lut = [
            1 => SettingServicePriceService::class,
            2 => SettingServicePriceService::class,
            3 => SettingServicePriceService::class,
            4 => SettingLongtermPriceService::class,
//            5 => SettingClockPriceService::class,
        ];

        $collection = new Collection($lut);
        $appClass = $collection->get($call_type, null);
        if(!is_null($appClass)){
            $this->app = app($appClass);
        }

        return $this;
    }

    public function fetchByHour(int $city_id = 1, int $hour = null){

        return $this->app->fetchByHour($city_id, $hour);
    }


}