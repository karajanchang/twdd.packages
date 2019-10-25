<?php


namespace Twdd\Services\Payments;


class Cash extends PaymentAbstract implements PaymentInterface
{
    public function back(){

    }

    public function cancel(){

    }

    public function pay(array $params = []){
        $OrderNo = $this->getOrderNo();
        $msg = '付現成功 (單號：' . $this->task->id . ')';

        return $this->returnSuccess($OrderNo, $msg, null);
    }

    public function query(){

    }
}