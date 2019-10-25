<?php


namespace Twdd\Services\Payments;


use Illuminate\Database\Eloquent\Model;
use Twdd\Errors\PaymentErrors;
use Twdd\Repositories\TaskPayLogRepository;

class PaymentAbstract
{
    protected $error;
    protected $task;
    private $taskPayLogRepository;
    protected $money = 0;

    public function __construct(PaymentErrors $error, TaskPayLogRepository $taskPayLogRepository)
    {
        $this->error = $error;
        $this->taskPayLogRepository = $taskPayLogRepository;
    }

    public function task(Model $task){
        $this->task = $task;
        $this->setMoney($task->TaskFee);

        return $this;
    }

    public function setMoney(int $money = 0){
        $this->money = $money;

        return $this;
    }

    public function getMoney(){

        return $this->money;
    }

    protected function returnError(string $OrderNo, int $error_code, string $msg = null, $result = null){
        $this->log(false, $msg, $result, $error_code);

        return [
            'error' => $this->error[$error_code],
            'OrderNo' => $OrderNo,
            'msg' => $msg,
            'result' => $result,
        ];
    }

    protected function returnSuccess(string $OrderNo, string $msg = null, $result = null){
        $this->log(true, $msg, $result);

        return [
            'OrderNo' => $OrderNo,
            'msg' => $msg,
            'result' => $result,
        ];
    }

    private function log(bool $pay_status, string $msg = null, $obj = null, int $error_code = null){
        $params = [
            'pay_status' => $pay_status,
            'error_code' => $error_code,
            'msg'=> $msg,
            'obj' => json_encode([$obj], JSON_UNESCAPED_UNICODE),
        ];
        $this->taskPayLogRepository->insertByTask($this->task, $params);
    }

    protected function getOrderNo(bool $is_random_serial = false){
        $TaskNo = str_pad($this->task->id, 8, '0', STR_PAD_LEFT);
        $OrderNo = $is_random_serial===false    ?   $TaskNo :   $TaskNo.'_'.rand(10, 99);

        return $OrderNo;
    }
}