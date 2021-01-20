<?php


namespace Twdd\Helpers;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Twdd\Http\Traits\ValidateTrait;
use Twdd\Services\Price\SettingLongtermPriceService;
use Twdd\Services\Price\SettingServicePriceService;

class SettingPriceServiceHelper
{
    use ValidateTrait;

    private $app;

    public function common(int $city_id = 1){
        $app = app(SettingServicePriceService::class);

        return $app->all($city_id);
    }

    public function longterm(int $city_id = 1){
        $app = app(SettingLongtermPriceService::class);

        return $app->all($city_id);
    }

    public function latlonzip($lat, $lon, string $zip = null){
        $city_id = 1;
        try {
            $city_id = $this->getCityId($lat, $lon, $zip);
        }catch (\Exception $e){
            Log::error(__CLASS__.'::'.__METHOD__.' 發生錯誤: ', [$e]);
        }
        $prices = [];

        $prices['common_price'] = $this->common($city_id);
        $prices['longterm_price'] = $this->longterm($city_id);

        return $prices;
    }

    private function getCityId($lat, $lon, string $zip = null){
        $latLonService = app(LatLonService::class);
        $location = $latLonService->citydistrictFromLatlonOrZip($lat, $lon, $zip);
        if(isset($location['city_id'])){

            return $location['city_id'];
        }

        return 1;
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
        $appClass = $collection->get($call_type, 1);
        if(!is_null($appClass)){
            $this->app = app($appClass);
        }

        return $this;
    }

    public function fetchByHour(int $city_id = null, int $hour = null){
        $city_id = is_int($city_id) ? $city_id : 1;

        return $this->app->fetchByHour($city_id, $hour);
    }

}