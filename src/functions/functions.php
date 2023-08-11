<?php
//---得到國泰的Bank Account
use Illuminate\Support\Carbon;
use Twdd\Facades\LatLonService;
use Twdd\Facades\SettingPriceService;
use Twdd\Facades\TokenService;
use Twdd\Facades\TwddCache;

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
        //---天使卡
        if (isset($task->member->member_grade_id) && $task->member->member_grade_id==env('BLACK_MEMBER_GRADE_ID', 5)) {
            //--- 天使卡-黑帽客要收系統平台費
            if ($task->call_type == 5) {
                return true;
            }
            return false;
        }

        $ts = $task->TaskRideTS ?? time();
        return IsTimestampChargeTwddFee($ts);
    }
}

if (!function_exists('IsTimestampChargeTwddFee')) {
    function IsTimestampChargeTwddFee($ts){
        //--不收的時段
        $NO_CHARGE_TWDD_FEEs = [];
        $NO_CHARGE_TWDD_FEE = env('NO_CHARGE_TWDD_FEE');
        if(strlen($NO_CHARGE_TWDD_FEE)>0){
            $NO_CHARGE_TWDD_FEEs = explode(',', $NO_CHARGE_TWDD_FEE);
        }
        if(count($NO_CHARGE_TWDD_FEEs)==2){
            if($ts >= $NO_CHARGE_TWDD_FEEs[0] && $ts <= $NO_CHARGE_TWDD_FEEs[1]){

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
                app(\Twdd\Helpers\TwddCache::class)->member($task->member->id)->MemberProfile()->key('MemberProfile', $task->member->id)->forget();
                app(\Twdd\Helpers\TwddCache::class)->member($task->member->id)->LastCoupon()->key('LastCoupon', $task->member->id)->forget();
            }
            app(\Twdd\Helpers\TwddCache::class)->DriverTask()->key('Task', $task->id)->forget();
        }
    }
}

/*
 * 清除DriverProfile的Cache
 */
if (!function_exists('ClearDriverProfileCache')) {
    function ClearDriverProfileCache(int $driver_id){
        TwddCache::driver($driver_id)->DriverProfile()->key('DriverProfile', $driver_id)->forget();
    }
}

//---取得該任務的分潤比率 PriceShare
if (!function_exists('TaskPriceShare')) {
    function TaskPriceShare(\Illuminate\Database\Eloquent\Model $task){
        $city_id = TaskStartCityId($task);
        $call_type = empty($task->call_type) ? 1 : (int) $task->call_type;

        $hour = Carbon::createFromTimestamp($task->TaskStartTS)->format('G');
        if ($call_type == 5) {
            return 0.8;
        }

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

//--清除 driver 登錄
if (!function_exists('DriverLogout')) {
    function DriverLogout(int $driver_id){
        if(empty($driver_id)) return ;

        TokenService::driver()->forget('driver', $driver_id);
    }
}

//--清除 member 登錄
if (!function_exists('MemberLogout')) {
    function MemberLogout(int $member_id){
        if(empty($member_id)) return ;

        TokenService::member()->forget('member', $member_id);
    }
}

//-- 取得底層 twdd config
// twdd_config('task.type')->search('電話'); // 取得 2
// twdd_config('task.type')->get(2); // 取得 電話
if (!function_exists('twdd_config')) {
    function twdd_config($_key, $_default = null)
    {
        $config = new \Illuminate\Config\Repository();

        $files = [];

        $configPath = base_path().DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,['vendor', 'twdd', 'packages', 'src', 'config']);

        foreach (\Symfony\Component\Finder\Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $files[basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        if ($files) {
            foreach ($files as $key => $path) {
                $config->set($key, require $path);
            }
        }

        return $config->get($_key, $_default);
    }
}

/*
 * http 產生錯誤
 */
if(!function_exists('responseError')){
    function responseError($message, int $code){
        if(!function_exists('response')) {
            if(is_string($message)) {
                return ['error' => [
                    'message' => $message,
                    'code' => $code,
                ]
                ];
            }else{
                $array = (array) $message;
                $code = ['code' => $code];
                return ['error' =>
                    array_merge($array, $code)
                ];
            }
        }else{
            if(is_string($message)) {

                return response()->json(compact('message'))->setStatusCode($code);
            }else{

                return response()->json($message)->setStatusCode($code);
            }
        }
    }
}
