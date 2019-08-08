<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 11:51
 */

namespace Twdd\Services\Coupon\Batch;


use Twdd\Helpers\CouponFactory;

class CouponBatch extends AbstractCouponBatch implements InterfaceCouponBatch
{

    public function init(CouponFactory $couponFactory){
        
        return $this->couponService()
                    ->member($couponFactory->member)
                    ->members($couponFactory->members)
                    ->user($couponFactory->user)
                    ->create($couponFactory->params);
    }
}
