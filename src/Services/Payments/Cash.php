<?php


namespace Twdd\Services\Payments;


class Cash extends PaymentAbstract implements PaymentInterface
{
    public function back(int $amt, bool $is_notify_member = false){
        $this->setMoney($amt);
        $msg = '付現退回成功 (單號：' . $this->task->id . ')';

        return $this->returnSuccess($msg, null, true);
    }

    public function cancel(){

    }

    public function pay(array $params = [], bool $is_notify_member = true){
        $msg = '付現成功 (單號：' . $this->task->id . ')';

        return $this->returnSuccess($msg, null, true);
    }

    public function query(){

    }
}