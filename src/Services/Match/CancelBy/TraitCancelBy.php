<?php


namespace Twdd\Services\Match\CancelBy;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twdd\Models\Calldriver;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Repositories\CouponRepository;
use Twdd\Repositories\TaskCancelLogRepository;
use Twdd\Repositories\TaskRepository;

trait TraitCancelBy
{
    private $calldriverTaskMap;
    private $task;
    private $fees;

    public function cancelWithCheck(array $params = null, bool $is_force_cancel = false){

        return $this->cancel($params, $is_force_cancel);
    }
    /*
     * --取消
     * @param $params 參數
     * $param $is_force_cancel 強制取消
     */
    public function cancel(array $params = null, bool $is_force_cancel = false, bool $is_with_check = false){
        //--map和task的檢查
        $this->initMapAndTask();

        //--檢查是否可以取消
        $res = $this->check();
        if($res === false){

            return $res;
        }

        $this->fees = $this->shouldTakeCancelFee();

        $this->if_need_create_task();

        $params = $this->processParams($params);

        try {
            DB::beginTransaction();
            $this->cancelCalldriverTaskMap($params);

            //---有任務才去做以下動作
            if(!is_null($this->task)) {
                $this->cancelTask($params);


                //--清除cache
                ClearTaskCache($this->task);

                //---寫到task_cancel_logs裡
                $this->writeLog($params);
            }


            DB::commit();

            return true;
        }catch (\Exception $e){
            Log::error(__CLASS__.'::'.__METHOD__.' error: ', [$e]);
            DB::rollBack();

            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getCalldriverTaskMap()
    {
        return $this->calldriverTaskMap;
    }

    /**
     * @param mixed $calldriverTaskMap
     */
    public function calldriverTaskMap($calldriverTaskMap)
    {
        $this->calldriverTaskMap = $calldriverTaskMap;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param mixed $task
     */
    public function task($task)
    {
        $this->task = $task;

        return $this;
    }

    /*
     * map和task的檢查
     *
     */
    private function initMapAndTask(){
        if(is_null($this->calldriverTaskMap) && !is_null($this->task)){
            $this->calldriverTaskMap = app(CalldriverTaskMapRepository::class)->firstFromTaskId($this->task->id);
        }

        if(is_null($this->calldriverTaskMap)){

            throw new \Exception('沒有代入calldriverTaskMap');
        }
    }

    /*
     * 預約要返回coupon
     */
    private function unUsedCoupon(){
        if(!is_null($this->task->UserCreditCode) && $this->task->UserCreditCode != '') {
            app(CouponRepository::class)->setUnUsed($this->task->member_id, $this->task->UserCreditCode);
        }
    }

    /*
     * 塞入 task_cancel_logs
     */
    private function writeLog(array $params = null){
        Log::info(__CLASS__.'::'.__METHOD__.' params: ', $params);

        return app(TaskCancelLogRepository::class)->create($params);
    }

    /**
     * 應該要收取消費
     * $cancel_type 1一般預約  2 還未建立任務  3已建立任務
     * @return array|false
     */
    public function shouldTakeCancelFee(){
        $ts_long = env('VIOLATION_HOUR_LONG', 12) * 3600;
        $ts_short = env('VIOLATION_HOUR_SHORT', 1) * 3600;
        $diff = $this->calldriverTaskMap->TS - Carbon::now()->timestamp;

        $calldriver = $this->calldriverTaskMap->calldriver;

        $calldriverTaskMap = CalldriverTaskMap::find($this->calldriverTaskMap->id, ['id', 'task_id']);

        //--企業簽單
        if($calldriver->pay_type==3) {
            if ($diff < $ts_long) {
//                服務前12小時內 取消收取$150預約臨時取消費，並收取20%系統費，不收保險費
                $fee = env('VIOLATION_FEE_LONG', 150);

                //--駕駛啟動任務(taskstate >1)後取消收取$300預約臨時取消費，並收取20%系統費，不收保險費
                if (isset($calldriverTaskMap->task_id) && !empty($calldriverTaskMap->task_id) && $diff < $ts_short){
                    $fee = env('VIOLATION_FEE_SHORT', 300);
                }

                return [
                    'TaskFee' => $fee,
                    'twddFee' => $fee * 0.2,
                    'task_id' => $calldriverTaskMap->task_id ?? 0,
                ];
            }
        }else{
            //--預約代駕
            if($calldriver->call_type==2 && $diff < $ts_short){
                $fee = env('VIOLATION_PREMATCH_FEE_LONG', 100);

                return [
                    'TaskFee' => $fee,
                    'twddFee' => $fee * 0.2,
                    'task_id' => $calldriverTaskMap->task_id ?? 0,
                ];
            }
        }

       return [
            'TaskFee' => 0,
            'twddFee' => 0,
            'task_id' => $calldriverTaskMap->task_id ?? 0,
        ];
    }

    /**
     * 若有需要建立一筆任務
     * @param CalldriverTaskMap $map
     * @return null
     */
    private function if_need_create_task(){
        $task = null;
        $map = $this->calldriverTaskMap;
        if($this->fees['task_id']==0) {
            $parmas = [
                'type' => $map->calldriver->type,
                'call_type' => $map->calldriver->call_type,
                'pay_type' => $map->calldriver->pay_type,


                'TaskFee' => $this->fees['TaskFee'],
                'twddFee' => $this->fees['twddFee'],
                'member_id' => $map->calldriver->member_id,
                'driver_id' => $map->dirver_id,
                'TaskState' => 7,
                'is_user_violation' => 1,
                'createtime' => Carbon::now(),
                'extra_price' => 0,
                'over_price' => 0,
                'is_used_gold' => 0,
                'is_lock' => 0,
                'is_user_rate' => 0,
                'keyin_mile_unit' => 1,
                'is_unusual' => 0,
                'TaskStartWaitInterval' => 0,
                'car_factory_id' => $map->calldriver->car_factory_id ?? 0,
                'car_factory_pay_type' => $map->calldriver->car_factory_pay_type ?? 0,

                'TaskRideTS' => $map->TS,
                'TaskCancelTS' => Carbon::now()->timestamp,
                'TaskStartLat' => $map->calldriver->lat,
                'TaskStartLon' => $map->calldriver->lon,
                'start_zip' => $map->calldriver->zip,
                'TaskEndLat' => $map->calldriver->lat_det,
                'TaskEndLon' => $map->calldriver->lon_det,
                'end_zip' => $map->calldriver->zip_det,
                'UserAddress' => $map->calldriver->addr,
                'UserAddressKey' => $map->calldriver->addrKey,
                'UserRemark' => $map->calldriver->UserRemark,
                'UserCity' => $map->calldriver->city,
                'UserDistrict' => $map->calldriver->district,
                'DestCity' => $map->calldriver->city_det,
                'DestDistrict' => $map->calldriver->district_det,
                'DestAddress' => $map->calldriver->addr_det,
                'DestAddressKey' => $map->calldriver->addrKey_det,
                'call_driver_id' => $map->calldriver->city,

            ];

            $task = app(TaskRepository::class)->create($parmas);
            $this->task = $task;
            $this->calldriverTaskMap->task_id = $task->id;

            app(CalldriverTaskMap::class)->where('id', $this->calldriverTaskMap->id)->update([
                'task_id' => $task->id,
            ]);
        }

        return $task;
    }

    private function cancelOtherMap(int $cancel_reason_id = null){
        $calldriver = $this->calldriverTaskMap->calldriver;
        //--如果是車廠，把有關連的map也取消
        if($calldriver->type==10){
            app(CalldriverTaskMapRepository::class)->cancelOtherSameCalldriverId($calldriver->id, $this->calldriverTaskMap->id, $this->cancel_by, $cancel_reason_id);
        }
    }
}
