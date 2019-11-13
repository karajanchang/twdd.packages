<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:35
 */
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class SettingPriceService extends Facade
{
    protected static function getFacadeAccessor() { return 'SettingPriceService'; }
}

/*
 * $rows = SettingPriceService::common($city_id);
 * $rows = SettingPriceService::longterm($city_id);
 * $settingPrice = SettingPriceService::callType(1)->fetchByHour($city_id);
 * SettingPriceService::latlonzip($lat, $lon, $zip = null);
 */

/*
 * ##1. 依city_id得到一般服務費計算列表
    $rows = SettingPriceService::common($city_id);

##2. 依city_id得到長途服務費計算列表
    $rows = SettingPriceService::longterm($city_id);

##3. 依city_id得到該時段該call_type服務費
    $settingPrice = SettingPriceService::callType(1)->fetchByHour($city_id);

##4. 依lat,lon或zip去得到服務費計算
    $prices = SettingPriceService::latlonzip($lat, $lon, $zip = null);

    $prices = [
        'common_price' => [一般服務費],
        'longterm_price' => [長途服務費],
    ];

 */

