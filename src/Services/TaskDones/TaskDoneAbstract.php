<?php


namespace Twdd\Services\TaskDones;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Twdd\Events\TaskDoneEvent;
use Twdd\Facades\DriverService;
use Twdd\Facades\LatLonService;
use Twdd\Facades\SettingPriceService;
use Twdd\Repositories\DriverCreditChangeRepository;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\TaskRepository;

class TaskDoneAbstract
{
    protected $DriverCredit;
    protected $driverCreditChangeRepository;
    protected $driverRepository;
    protected $is_first_use = 0;
    protected $task;
    protected $TaskFee = 0;
    protected $twddFee = 0;
    protected $price_share = 0.8;
    protected $taskRepository;
    protected $member_creditcard_id = null;


    public function __construct(DriverCreditChangeRepository $driverCreditChangeRepository, DriverRepository $driverRepository, TaskRepository $taskRepository)
    {
        $this->driverCreditChangeRepository = $driverCreditChangeRepository;
        $this->driverRepository = $driverRepository;
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
        $this->onlineDriver();

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
        if(time() >= env('SHORT_FEE_CHANGE_START_TIMESTAMP', 1573444800) && time() <= env('SHORT_FEE_CHANGE_END_TIMESTAMP', 1893427200) && (int)$task->TaskDistance<=3000){
            $this->doCreditChange(15, round($task->TaskFee * 0.1), '短程津貼');
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

        return $this->taskRepository->isPay($this->task->id, $this->TaskFee, $this->twddFee, $this->is_first_use, $this->member_creditcard_id);
    }

    private function doCalucate(){
        $this->calucateTaskFee();
        $this->calucateTwddFee();
        $this->getPriceShare();

        $this->task->TaskFee = $this->TaskFee;
        $this->task->twddFee = $this->twddFee;
    }

    //--費用
    private function calucateTaskFee(){
        $this->TaskFee = $this->task->TaskStartFee + (int) $this->task->TaskDistanceFee + (int) $this->task->TaskWaitTimeFee + (int) $this->task->over_price + (int) $this->task->extra_price - (int) $this->task->UserCreditValue;
        if($this->TaskFee<0){
            $this->TaskFee = 0;
        }
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
            $credit = $back * $this->price_share;
            $this->doCreditChange(9, $credit);
        }
    }

    //---檢查是否要收系統費
    private function chargeTwddFee(){
        //---黑卡
        if ($this->task->member->member_grade_id==5) {

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

    private function getPriceShare(){
        $city_id = $this->getCityId();
        $call_type = empty($this->task->call_type) ? 1 : (int) $this->task->call_type;
        $settingPrice = SettingPriceService::callType($call_type)->fetchByHour($city_id);

        $column = $this->task->pay_type==2 ? 'price_share_creditcard' : 'price_share';
        if(isset($settingPrice->$column) && $settingPrice->$column > 0){

            $this->price_share = $settingPrice->$column;
        }
    }

    private function getCityId(){
        if(isset($this->task->start_city_id) && $this->task->start_city_id > 0){

            return $this->task->start_city_id;
        }
        $start_zip =  isset($this->task->start_zip) ? $this->task->start_zip : null;

        $cityDistrict = LatLonService::citydistrictFromLatlonOrZip($this->task->UserLat, $this->task->UserLon, $start_zip);
        if(isset($cityDistrict['city_id'])){

            return $cityDistrict['city_id'];
        }

        return 1;
    }


}