<?php
//---得到國泰的Bank Account
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Twdd\Facades\LatLonService;
use Twdd\Facades\SettingPriceService;

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

//---取得該任務的分潤比率 PriceShare
if (!function_exists('TaskPriceShare')) {
    function TaskPriceShare(\Illuminate\Database\Eloquent\Model $task){
        $city_id = TaskStartCityId($task);
        $call_type = empty($task->call_type) ? 1 : (int) $task->call_type;

        $hour = Carbon::createFromTimestamp($task->TaskStartTS);

        $settingPrice = SettingPriceService::callType($call_type)->fetchByHour($city_id, $hour);

        $column = $task->pay_type==2 ? 'price_share_creditcard' : 'price_share';
        if(!empty($settingPrice->$column)){

            return $settingPrice->$column;
        }

        return 0.8;
    }
}
//---取得該任務的StartCityId
if (!function_exists('TaskStartCityId')) {
    function TaskStartCityId(\Illuminate\Database\Eloquent\Model $task){
        if(isset($task->start_city_id) && $task->start_city_id > 0){

             return $task->start_city_id;
        }

        $start_zip =  isset($task->start_zip) ? $task->start_zip : null;
        $cityDistrict = LatLonService::citydistrictFromLatlonOrZip($task->UserLat, $task->UserLon, $start_zip);
        if(isset($cityDistrict['city_id'])){

            return $cityDistrict['city_id'];
        }

        return 1;
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
