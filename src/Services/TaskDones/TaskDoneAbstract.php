<?php


namespace Twdd\Services\TaskDones;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Twdd\Events\TaskDoneEvent;
use Twdd\Facades\DriverService;
use Twdd\Facades\LatLonService;
use Twdd\Facades\SettingPriceService;
use Twdd\Repositories\DriverCreditChangeRepository;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\TaskRepository;
use Twdd\Services\Price\SettingServicePriceService;

class TaskDoneAbstract
{
    protected $DriverCredit;
    protected $driverCreditChangeRepository;
    protected $driverRepository;
    protected $settingServicePriceService;
    protected $is_first_use = 0;
    protected $task;
    protected $TaskFee = 0;
    protected $twddFee = 0;
    protected $price_share = 0.8;
    protected $taskRepository;
    protected $member_creditcard_id = null;


    public function __construct(DriverCreditChangeRepository $driverCreditChangeRepository, DriverRepository $driverRepository,
                                TaskRepository $taskRepository, SettingServicePriceService $settingServicePriceService)
    {
        $this->driverCreditChangeRepository = $driverCreditChangeRepository;
        $this->driverRepository = $driverRepository;
        $this->settingServicePriceService = $settingServicePriceService;
        $this->taskRepository = $taskRepository;
    }

    public function setTask(Model $task, int $member_creditcard_id = 0){
        $this->task = $task;
        $this->DriverCredit = $task->driver->DriverCredit;
        $this->member_creditcard_id = $member_creditcard_id;
        $this->doCalucate();

        return $this;
    }

    public function getTask(){

        return $this->task;
    }

    //--最後要處理的
    protected function lastProcess(){
        //---首次使用增加記錄
        if($this->task->member->nums7==1) {
            $this->is_first_use = 1;
        }

        //----保險出險退回
        $this->doDriverInsuranceBack();

        //--短程費用調為300的補貼
        $this->doShortDistanceBack();


        //---更新夥伴的DriverCredit
        $this->driverRepository->modDriverCredit($this->task->driver_id, $this->DriverCredit);

        //--更新Task
        $this->updateTaskDone();

        //---把司機設為上線
        //$this->onlineDriver();

        //--Event
        Event(new TaskDoneEvent($this->getTask()));
    }

    //---把司機設為上線
    private function onlineDriver(){
        if(empty($this->task->driver->location->DriverLat) || empty($this->task->driver->location->DriverLon) ){

            return ;
        }
        $location = LatLonService::citydistrictFromLatlonOrZip($this->task->driver->location->DriverLat, $this->task->driver->location->DriverLon);
        $params = [
            'lat' => $this->task->driver->location->DriverLat,
            'lon' => $this->task->driver->location->DriverLon,
            'zip' => $location['zip'],
        ];
        DriverService::driver($this->task->driver)->online($params);
    }

    private function doDriverInsuranceBack(){
        $driverInsuranceBackService = app(DriverInsuranceBackService::class)->task($this->getTask());
        $res = $driverInsuranceBackService->cost();
        if(isset($res['InsuranceBack']) && $res['InsuranceBack'] < 0){
            $this->doCreditChange(13, $res['InsuranceBack'], '司機保險出險費');
        }
    }

    //--短程費用調為300的補貼
    private function doShortDistanceBack(){
        $task = $this->getTask();
        if( (int)$task->TaskDistance<=3000 ){
            //--第一次
            if (time() >= env('SHORT_FEE_CHANGE_START_TIMESTAMP', 1573444800) && time() <= env('SHORT_FEE_CHANGE_END_TIMESTAMP', 1893427200)){
                $this->doCreditChange(15, round($task->TaskFee * 0.1), '短程津貼');
            }
            //--第二次
            if (time() >= env('SHORT_FEE_CHANGE_START_TIMESTAMP2', 1588262400) && time() <= env('SHORT_FEE_CHANGE_END_TIMESTAMP2', 1596211199)){
                $this->doCreditChange(15, round($task->TaskFee * 0.05), '短程津貼');
            }
        }
    }

    protected function doCreditChange(int $type, int $credit, string $comments = null){
        $params = [
            'driver_id' => $this->task->driver_id,
            'task_id' => $this->task->id,
            'type' => $type,
            'credit' => $credit,
            'driver_credit_before' => $this->DriverCredit,
            'driver_credit_after' => $this->DriverCredit + $credit,
            'comments' => $comments,
            'createtime' => Carbon::now()->toDateTimeString(),
        ];
        $this->DriverCredit = $this->DriverCredit + $credit;

        $this->driverCreditChangeRepository->insert($params);

    }

    private function updateTaskDone(){

        return $this->taskRepository->isPay($this->task, $this->TaskFee, $this->twddFee, $this->is_first_use, $this->member_creditcard_id);
    }

    private function doCalucate(){
        $this->getPriceShare();
        $this->calucateTaskFee();
        $this->calucateTwddFee();

        $this->task->TaskFee = $this->TaskFee;
        $this->task->twddFee = $this->twddFee;
    }

    //--費用
    private function calucateTaskFee(){
        //---有收系統費的才再算一次
        if($this->chargeTwddFee()===true){
            $this->TaskFee = $this->task->TaskStartFee + (int) $this->task->TaskDistanceFee + (int) $this->task->TaskWaitTimeFee + (int) $this->task->over_price + (int) $this->task->extra_price - (int) $this->task->UserCreditValue;
        }else{
            $this->TaskFee = $this->task->TaskFee;
        }
        if($this->TaskFee<0){
            $this->TaskFee = 0;
        }
        Log::info('TaskDoneAbstract ', ['TaskFee' => $this->TaskFee, 'task' => $this->task->TaskFee]);
    }

    //--系統費
    private function calucateTwddFee(){
        if($this->chargeTwddFee()===true){
            $this->twddFee = round($this->TaskFee * (1 - $this->price_share));
        }
    }

    //---優惠回補
    protected function calucateBackUserCreditValue(){
        $TotalFee = $this->task->TaskStartFee + (int) $this->task->TaskDistanceFee + (int) $this->task->TaskWaitTimeFee + (int) $this->task->over_price + (int) $this->task->extra_price;

        $back = $this->task->UserCreditValue > $TotalFee ? $TotalFee : $this->task->UserCreditValue;
        if($back>0){
            $credit = $back;
            //---不收系統費時完全回補
            if($this->chargeTwddFee()===true) {
                $credit = $back * $this->price_share;
            }
            $this->doCreditChange(9, $credit);
        }
    }

    /*
     * APIUSR-191 擴大媒合優惠回補
     * */
    protected function calcFarTaskCreditReward()
    {
        if ($this->task->call_far_driver == 0) {
            return ;
        }

        $cityId = $this->task->city_id ?? 1;
        $hour = Carbon::parse($this->task->TaskRideTS)->hour;
        $settingServicePrice = $this->settingServicePriceService->fetchByHour($cityId, $hour);
        if (empty($settingServicePrice)) {
            return ;
        }

        // 最大回饋公里
        $extraMatchDistanceDiff = min($this->task->matchDistance - $settingServicePrice->max_match_range , $settingServicePrice->extra_match_reward_max_distance);
        // 總單位回饋%數
        $rewardPercentage = ceil($extraMatchDistanceDiff / $settingServicePrice->extra_match_reward_unit_distance) * $settingServicePrice->extra_match_reward_unit_percentage;
        $reward = floor($rewardPercentage * ($this->task->TaskFee + (int) $this->task->UserCreditValue));
        $comment = '遠程津貼';
        $this->doCreditChange(9, $reward, $comment);
        Log::info('遠程津貼回補:' . $reward . ';單號:' . $this->task->id);
    }

    //---檢查是否要收系統費
    private function chargeTwddFee(){

        return IsTaskChargeTwddFee($this->task);
    }

    private function getPriceShare(){
        $this->price_share = TaskPriceShare($this->task);
        Log::info('TaskDoneAbstract 抓到了金額，單號('.$this->task->id.')', ['call_type' => $this->task->call_type, 'price_share' => $this->price_share]);
    }

    private function getCityId(){

        return TaskStartCityId($this->task);
    }


}
