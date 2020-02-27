<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 11:32
 */

namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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
    private $task = null;
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

    public function task(Model $task){
        $this->task = $task;
        $this->member($task->member);


        return $this;
    }

    public function check(string $UserCreditCode){

        $couponword = $this->couponwordService->fetch($UserCreditCode);
        //---couponword
        if(!isset($couponword['error'])){
            $res = $this->couponwordService->check($UserCreditCode, $this->member, $this->task);
            if(isset($res['error'])){
                $this->log('CouponValid null (couponwordService->check): ', $res['error']);

                return $res;
            }

            $res = $this->couponService->validCouponword($UserCreditCode, $this->member, $this->task);
            if (!empty($res->id)) {
                 Log::info('CouponValid null (couponService->validCouponWord): ', [$res]);

                return $res;
            }

            Log::info('CouponValid (couponService->validCouponWord return $couponwordService->check()): ', [$couponword]);
            return $couponword;
        }else{//--coupon
            $res = $this->couponService->check($UserCreditCode, $this->member, $this->task);

            if(isset($res['error'])) {
                $this->log('CouponValid (couponService->check): ', $res['error']);
            }

            return $res;
        }

    }

    private function log($msg, $error){
        try {
            Log::info($msg, ['error' =>
                [
                    'unit' => $error->getUnit(),
                    'code' => $error->getCode(),
                    'message' => $error->getMessage(),
                ]
            ]);
        }catch (\Exception $e){
            Log::error('CouponValid log exception: ', [$e]);
        }
    }
}
