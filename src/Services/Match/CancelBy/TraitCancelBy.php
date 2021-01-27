<?php


namespace Twdd\Services\Match\CancelBy;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Repositories\CouponRepository;
use Twdd\Repositories\TaskCancelLogRepository;

trait TraitCancelBy
{
    private $calldriverTaskMap;
    private $task;

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
        if(!is_null($this->task->UserCreditCode)) {
            app(CouponRepository::class)->setUnUsed($this->task->member_id, $this->task->UserCreditCode);
        }
    }

    /*
     * 塞入 task_cancel_logs
     */
    private function writeLog(array $params = null){

        return app(TaskCancelLogRepository::class)->create($params);
    }

}