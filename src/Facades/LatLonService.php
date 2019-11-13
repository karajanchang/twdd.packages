<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:22
 */

namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class LatLonService extends Facade {
    protected static function getFacadeAccessor() { return 'LatLonService'; }
}
/*
 *
 * ##1.從city和district得到該區的location
    $location = LatLonService::citydistrictFromCityAndDistrict('台北市', '中正區');

##2.從city和district得到該區的zip，透過line的json檔案
    $zip = LatLonService::zipFromDist('台北市', '中正區');

##1.從lat,lon或zip得到該區的location
    $location = LatLonService::citydistrictFromLatlonOrZip($lat, $lon, $zip = null);

 */




//----判斷此區是否在服務區域內
/*
$cityDistrict = LatLonService::citydistrictFromCityAndDistrict('台北市', '中正區');
//---區域不正確
if($cityDistrict['zip']==0){
}
$all = [
    'lat' => 25.0000,
    'lon' => 120.000,
    'zip' => $cityDistrict ->zip,
];
$serviceArea = TaskService::ServiceArea()->check($all);
*/