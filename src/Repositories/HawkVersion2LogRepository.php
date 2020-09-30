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

        return $this->where('member_id', $member_id)->whereNull('first_task_id_after_register')->first();

    }

    /*
     * 記錄用戶拿到邀請優惠的第一筆任務
     */
    public function logGetCouponInfoByMemberId(int $task_id, HawkVersion2Log $hawkVersion2Log = null){
        if(is_null($hawkVersion2Log)) return ;

        $hawkVersion2Log->first_task_id_after_register = $task_id;
        $hawkVersion2Log->save();
    }

}