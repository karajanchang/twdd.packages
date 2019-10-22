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