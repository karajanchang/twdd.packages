<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 12:13
 */

namespace Twdd\Services\Coupon\Batch;


use Twdd\Helpers\CouponFactory;

class CouponBatchFromCouponword extends AbstractCouponBatch implements InterfaceCouponBatch
{

    public function init(CouponFactory $couponFactory){
        $couponwordService = $this->couponwordService();
        if(!isset($couponFactory->params['couponword_id'])){

            return new \Exception("params must have couponword_id value");
        }
        if(is_null($couponFactory->member)){

            return new \Exception("must have member_id value");
        }

        $couponword = $couponwordService->find($couponFactory->params['couponword_id']);

        //---error
        if(!isset($couponword->id)){

            return $couponword;
        }


        $params = [
            'title' => $couponword->title,
            'code' => $couponword->code,
            'money' => $couponword->money,
            'startTS' => $couponword->startTS,
            'endTS' => $couponword->endTS,
            'only_first_use' => $couponword->only_first_use,
            'activity_id' => $couponword->activity_id,
        ];

        return $this->couponService()
            ->member($couponFactory->member)
            ->user($couponFactory->user)
            ->create($params);
    }
}
