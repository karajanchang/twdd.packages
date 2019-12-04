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
        if(empty($this->task->member)){

            return ;
        }
        $coupon = CouponValid::member($this->task->member)->check($this->task->UserCreditCode);
        if($coupon instanceof Coupon){
            $app = app()->make(CouponRepository::class);
            $res = $app->setUsed($coupon->id);
            if($res==0){
                Log::error('(CouponServiceHelper) 優惠券'.$this->task->UserCreditCode.'設為已使用沒有更新 任務id: '.$this->task->id);
            }
        }
    }
}