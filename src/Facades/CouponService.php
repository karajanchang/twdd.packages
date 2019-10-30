<?php

/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 16:20
 */

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class CouponService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CouponService';
    }
}

//---coupon設為已使用
/*
$res = CouponService::task($task)->setUsed();
*/
