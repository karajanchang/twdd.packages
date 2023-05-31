<?php


namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Twdd\Facades\CouponValid;
use Twdd\Models\Coupon;
use Twdd\Repositories\CouponRepository;

class CouponServiceHelper
{
    private $task;

    public function task(Model $task){
        $this->task = $task;

        return $this;
    }

    public function setUsed(){
        if(!isset($this->task->member)){

            return false;
        }

        if(empty($this->task->UserCreditCode) || strlen($this->task->UserCreditCode)==0){

            return false;
        }
        Log::info('CouponServiceHelper ('.$this->task->id.'): setUsed', [$this->task->UserCreditCode]);

        $coupon = app(CouponRepository::class)->fetch($this->task->UserCreditCode);

        Log::info('CouponServiceHelper ('.$this->task->id.'): setUsed', [$coupon]);
        if($coupon instanceof Coupon){
            $res = app(CouponRepository::class)->setUsed($coupon->id, $this->task->member_id);
            if($res==0){
                Log::error('(CouponServiceHelper) 優惠券'.$this->task->UserCreditCode.'設為已使用沒有更新 任務id: '.$this->task->id);
            }
        }
    }
}