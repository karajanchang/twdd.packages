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

    public function back(){

        return $this->payment->task($this->task)->back();
    }

    public function cancel(){

        return $this->payment->task($this->task)->cancel();
    }

    public function pay(array $params = []){

        return $this->payment->task($this->task)->pay($params);
    }

    public function query(){

        return $this->payment->task($this->task)->query();
    }


}

//---付款
/*
 * PayServie::by(2)->task($task)->pay($params);
 */