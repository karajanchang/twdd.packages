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
 * SettingPriceService::latlonzip($lat, $lon, $zip);
 */