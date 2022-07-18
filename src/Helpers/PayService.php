<?php


namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PayService
{
    //--本來的付款方式（為了車廠變動來記）
    private $original_pay_type = null;
    private $payment = null;
    private $task = null;
    private $calldriverTaskMap = null;
    private $call_type = null;

    public function __construct()
    {
        $this->lut = include_once __DIR__.'/../Services/Payments/config.php';
        $this->checkDoneIsExists();
    }

    private function checkDoneIsExists(){
        foreach($this->lut as $lut){
            $doneClass = str_replace('Payments', 'TaskDones', $lut);
            if(!class_exists($doneClass)){
                throw new \Exception('Please create done class: '.$doneClass);
            }
        }
    }

    public function callType(int $call_type)
    {
        $this->call_type = $call_type;

        return $this;
    }

    public function by(int $pay_type, bool $is_change = false) {

        if ($this->call_type === 5) {
            $this->payment = app(Collection::make($this->lut)->get(6));
        } else {
            $this->payment = app(Collection::make($this->lut)->get($pay_type));
        }
        Log::info('PayService::by', ['payment' => $pay_type]);

        //---原來的才記
        if($is_change===false) {
            Log::info('PayService::by', ['original_payment' => $pay_type]);
            $this->original_pay_type = $pay_type;
        }

        return $this;
    }

    public function task(Model $task){
        $this->task = $task;
        return $this;
    }

    public function calldriverTaskMap(Model $calldriverTaskMap){
        $this->calldriverTaskMap = $calldriverTaskMap;
        return $this;
    }

    public function back(int $amt, bool $is_notify_member = false){

        return $this->payment->task($this->task)->back($amt, $is_notify_member);
    }

    public function cancel(string $OrderNo = null, int $amount = null){
        Log::info(__CLASS__.'::'.__METHOD__.': ', [$this->task]);

        return $this->payment->task($this->task)->cancel($OrderNo, $amount);
    }

    /*
     * 如果是車廠的單要再把付款方式指定為車廠4
     */
    private function payWhenIsCarFactory(){
        if(isset($this->task->car_factory_id) && !empty($this->task->car_factory_id)) {
            //--APP送過來pay_type是現金
            if($this->original_pay_type==1) {
                $this->by(4, true);

                return  $this->updateCarFactoryPayType(1);
            }

            //--APP送過來pay_type是信用卡
            if($this->original_pay_type==2) {
                $this->by(4, true);

                return  $this->updateCarFactoryPayType(2);
            }
        }
    }

    /*
     * 更新task裡car_factory_pay_type
     */
    private function updateCarFactoryPayType(int $car_factory_pay_type){
        Log::info('車廠：car_factory_pay_type 修改為'. $car_factory_pay_type);
        $this->task->car_factory_pay_type = $car_factory_pay_type;
        $this->task->save();

        return null;
    }

    public function pay(array $params = [], bool $is_notify_member = true){
        $this->payWhenIsCarFactory();

        return $this->payment->calldriverTaskMap($this->calldriverTaskMap)->task($this->task)->pay($params, $is_notify_member);
    }

    public function query(){

        return $this->payment->task($this->task)->query();
    }

    public function money(int $money){
        $this->payment->setMoney($money);

        return $this;
    }

}

//---付款
/*
 * PayServie::by(2)->task($task)->pay($params);
 */
