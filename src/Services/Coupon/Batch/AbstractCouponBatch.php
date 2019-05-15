<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 12:25
 */

namespace Twdd\Services\Coupon\Batch;


class AbstractCouponBatch
{
    protected static $couponService = null;
    protected static $couponwordService = null;


    public function couponService(){
        if(static::$couponService==null){
            static::$couponService = app()->make(\Twdd\Services\Coupon\CouponService::class);
        }

        return static::$couponService;
    }

    public function couponwordService(){
        if(static::$couponwordService==null){
            static::$couponwordService = app()->make(\Twdd\Services\Coupon\CouponwordService::class);
        }

        return static::$couponwordService;
    }
}
