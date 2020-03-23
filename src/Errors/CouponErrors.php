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

    public function error500(){

        return trans('twdd::coupon.coupon_exception');
    }

    public function error4001(){

        return trans('twdd::coupon.coupon_does_not_exists');
    }

    public function error4002(){

        return trans('twdd::coupon.coupon_have_been_used');
    }

    public function error4003(){

        return trans('twdd::coupon.coupon_have_been_out_of_date');
    }

    public function error4004(){

        return trans('twdd::coupon.coupon_only_can_use_in_first_use');
    }

    public function error4005(){

        return trans('twdd::coupon.coupon_not_open_yet');
    }

    public function error4006(){

        return trans('twdd::coupon.coupon_only_for_custom_member');
    }

    public function error4007(){

        return trans('twdd::coupon.coupon_have_been_used');
    }

    public function error4008(){

        return trans('twdd::coupon.black_card_can_not_use_coupon');
    }
}
