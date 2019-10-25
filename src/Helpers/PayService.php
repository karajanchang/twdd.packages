<?php


namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Twdd\Services\Payments\Cash;
use Twdd\Services\Payments\Spgateway;

class PayService
{
    private $lut = [
        1 => Cash::class,
        2 => Spgateway::class,
    ];
    private $payment = null;
    private $task = null;

    public function __construct()
    {
        $this->lut = include_once __DIR__.'/../Services/Payments/config.php';
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