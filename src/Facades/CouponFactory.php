<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 16:20
 */

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class CouponFactory extends Facade
{
    protected static function getFacadeAccessor() { return 'CouponFactory'; }
}
