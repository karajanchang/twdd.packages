<?php


namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PayService
{
    private $payment = null;
    private $task = null;

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

    public function by(int $pay_type){
        $this->payment = app(Collection::make($this->lut)->get($pay_type));

        return $this;
    }

    public function task(Model $task){
        $this->task = $task;

        return $this;
    }

    public function back(int $amt, bool $is_notify_member = false){

        return $this->payment->task($this->task)->back($amt, $is_notify_member);
    }

    public function cancel(string $OrderNo = null, int $amount = null){
        if(!is_null($this->task)){
            $this->payment->task($this->task);
        }

        return $this->payment->cancel($OrderNo, $amount);
    }

    public function pay(array $params = [], bool $is_notify_member = true){

        return $this->payment->task($this->task)->pay($params, $is_notify_member);
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
