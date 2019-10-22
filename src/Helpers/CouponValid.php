<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 11:32
 */

namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Twdd\Errors\CouponErrors;
use Twdd\Repositories\DriverRepository;
use Twdd\Services\Coupon\CouponService;
use Twdd\Services\Coupon\CouponwordService;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class CouponValid extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $member = null;
    private $couponService = null;
    private $couponwordService = null;
    private $driverRepository = null;

    public function __construct(CouponErrors $error, CouponService $couponService, CouponwordService $couponwordService)
    {
        $this->couponService = $couponService;
        $this->couponwordService = $couponwordService;
        $this->error = $error;
    }


    public function member(Model $member){
        $this->member = $member;

        return $this;
    }

    public function check(string $UserCreditCode){
        $couponword = $this->couponwordService->check($UserCreditCode, $this->member);

        if(isset($couponword['error'])){
            $coupon = $this->couponService->check($UserCreditCode, $this->member);

            return $coupon;
        }else {
            if (isset($couponword->id) && $couponword->id > 0) {
                $coupon = $this->couponService->validCouponword($UserCreditCode, $this->member);
                if (isset($coupon->id) && $coupon->id > 0) {

                    return $coupon;
                }
            }
        }

        return $couponword;
    }

}
