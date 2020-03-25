<?php
//---得到國泰的Bank Account
if (!function_exists('BankAccount')) {
    function BankAccount($DriverID){
        if(!isset($DriverID)){

            return '';
        }
        $driverID = $DriverID;
        if($DriverID instanceof \Illuminate\Database\Eloquent\Model){
            $driverID = $DriverID->DriverID;
        }

        return app(\Twdd\Helpers\Bank::class)->account($driverID);
    }
}
//--檢查此任務是否需要收系統費
if (!function_exists('IsTaskChargeTwddFee')) {
    function IsTaskChargeTwddFee(\Illuminate\Database\Eloquent\Model $task){
        //---黑卡
        if (isset($task->member->member_grade_id) && $task->member->member_grade_id==env('BLACK_MEMBER_GRADE_ID', 5)) {

            return false;
        }

        //--不收的時段
        $NO_CHARGE_TWDD_FEEs = [];
        $NO_CHARGE_TWDD_FEE = env('NO_CHARGE_TWDD_FEE');
        if(strlen($NO_CHARGE_TWDD_FEE)>0){
            $NO_CHARGE_TWDD_FEEs = explode(',', $NO_CHARGE_TWDD_FEE);
        }
        if(count($NO_CHARGE_TWDD_FEEs)==2){
            $now = time();
            if($now >= $NO_CHARGE_TWDD_FEEs[0] && $now <= $NO_CHARGE_TWDD_FEEs[1]){

                return false;
            }
        }

        return true;
    }
}


//---這個function會清除Task所有的Cache
if (!function_exists('ClearTaskCache')) {
    function ClearTaskCache(\Illuminate\Database\Eloquent\Model $task)
    {
        if(!empty($task->id)) {
            if (!empty($task->driver->id)) {
                app(\Twdd\Helpers\TwddCache::class)->driver($task->driver->id)->DriverLastTask()->key('DriverLastTask', $task->driver->id)->forget();
                app(\Twdd\Helpers\TwddCache::class)->driver($task->driver->id)->MonthMoneyDriver($task->driver->id)->key('MonthMoneyDriver', $task->driver->id)->forget();
            }
            if (!empty($task->member->id)) {

            }
            app(\Twdd\Helpers\TwddCache::class)->DriverTask()->key('Task', $task->id)->forget();
        }
    }
}
//---把 任務單號 123 轉成補0的字串 00000123
if (!function_exists('TaskNo')) {
    function TaskNo($task){
        if(!isset($task)){

            return '';
        }

        $task_id = $task;
        if($task instanceof \Illuminate\Database\Eloquent\Model){
            $task_id = $task->id;
        }

        return app(\Twdd\Services\Task\TaskNo::class)::make($task_id);
    }
}
