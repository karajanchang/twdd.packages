<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;


class CouponErrors extends ErrorAbstract
{
    protected $unit = 'coupon';

    public function error4001(){

        return trans('twdd::errors.coupon_does_not_exists');
    }

    public function error4002(){

        return trans('twdd::errors.coupon_have_been_used');
    }

    public function error4003(){

        return trans('twdd::errors.coupon_have_been_out_of_date');
    }

    public function error4004(){

        return trans('twdd::errors.coupon_only_can_use_in_first_use');
    }

    public function error4005(){

        return trans('twdd::errors.coupon_not_open_yet');
    }

}
