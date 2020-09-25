<?php


namespace Twdd\Repositories;


use Carbon\Carbon;
use Twdd\Models\HawkVersion2Log;
use Zhyu\Repositories\Eloquents\Repository;

class HawkVersion2LogRepository extends Repository
{
    public function model()
    {
        return HawkVersion2Log::class;
    }

    /*
     * 用戶還沒拿過邀請優惠
     */
    public function fetchHaveNotGotHawkVersion2CouopnByMemberId(int $member_id){

        return $this->where('member_id', $member_id)->whereNull('take_coupon_id')->first();

    }

    /*
     * 記錄用戶拿到邀請優惠
     */
    public function logGetCouponInfoByMemberId(int $task_id, int $coupon_id, HawkVersion2Log $hawkVersion2Log = null){
        if(is_null($hawkVersion2Log) || $coupon_id==0) return ;

        $hawkVersion2Log->take_coupon_by_task_id = $task_id;
        $hawkVersion2Log->take_coupon_id = $coupon_id;
        $hawkVersion2Log->take_coupon_at = Carbon::now();
        $hawkVersion2Log->save();
    }

}