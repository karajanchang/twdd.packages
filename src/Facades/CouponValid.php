<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 16:20
 */

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class CouponValid extends Facade
{
    protected static function getFacadeAccessor() { return 'CouponValid'; }
}
//---判斷此coupon或couponword是否有效
/*
$member = $this->getMember();
$res = CouponValid::member($member)->check('PFPJM4A5J');
*/