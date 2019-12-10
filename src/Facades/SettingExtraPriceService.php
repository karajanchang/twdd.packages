<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:35
 */
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class SettingExtraPriceService extends Facade
{
    protected static function getFacadeAccessor() { return 'SettingExtraPriceService'; }
}


/*
 * SettingExtraPriceService::getByLatLonOrZip(25.0389555, 121.5272498, 100);
 *
 *  [
              'sum'  => 350,
              'results' => [
                        'id' => 1,
                         'name' => '連續價日',
                         'msg' => '春節加價150元',
                         'startTS' => 123,
                          'endTS' => 124,
                         'price' => 150,
                     ]]
               ]
 *
 * SettingExtraPriceService::clearCache();
 *
 *
 */

