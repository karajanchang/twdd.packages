<?php


namespace Twdd\Services\Payments;


class Cash extends PaymentAbstract implements PaymentInterface
{
    public function back(){

    }

    public function cancel(){

    }

    public function pay(array $params = []){


        $msg = '付現成功 (單號：' . $this->task->id . ')';

        return $this->returnSuccess($msg, null, true);
    }

    public function query(){

    }
}