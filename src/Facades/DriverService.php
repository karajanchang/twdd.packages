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
