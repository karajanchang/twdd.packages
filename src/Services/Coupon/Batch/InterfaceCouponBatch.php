<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 12:14
 */

namespace Twdd\Services\Coupon\Batch;


use Twdd\Helpers\CouponFactory;

Interface InterfaceCouponBatch
{
    public function init(CouponFactory $couponFactory);
}
