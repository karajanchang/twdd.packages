<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:22
 */

namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class DriverService extends Facade {
    protected static function getFacadeAccessor() { return 'DriverService'; }
}

//----讓駕駛任務中
//DriverService::driver($driver)->intask();

//----讓駕駛上線
//DriverService::driver($driver)->online();

//----讓駕駛下線
//DriverService::driver($driver)->offline();
