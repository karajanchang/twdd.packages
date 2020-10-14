<?php


namespace Twdd\Services\Payments;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Twdd\Errors\PaymentErrors;
use Twdd\Facades\TaskNo;
use Twdd\Repositories\TaskPayLogRepository;

class PaymentAbstract
{
    protected $error;
    protected $task;
    private $taskPayLogRepository;
    protected $money = 0;
    protected $pay_type = 1;
    protected $OrderNo = null;
    protected $member_creditcard_id = null;

    public function __construct(PaymentErrors $error, TaskPayLogRepository $taskPayLogRepository)
    {
        $this->error = $error;
        $this->taskPayLogRepository = $taskPayLogRepository;
    }

    public function task(Model $task = null){
        $this->task = $task;
        $this->setOrderNo();
        if(!is_null($task) && !empty($task->TaskFee)) {
            $this->setMoney($task->TaskFee);
        }

        return $this;
    }

    public function setMoney(int $money = 0){
        $this->money = $money;

        return $this;
    }

    public function getMoney(){

        return $this->money;
    }

    protected function returnError(int $error_code, string $msg = null, $result = null, bool $is_log = false, bool $is_payment_timeout = false){
        if($is_log===true) {
            $pay_status = 0;
            if($is_payment_timeout===true){
                $pay_status = 2;
            }
            $this->log($pay_status, $msg, $result, $error_code);
        }

        return [
            'error' => $this->error->_($error_code),
            'OrderNo' => $this->getOrderNo(),
            'msg' => $msg,
            'result' => $result,
            'amt' => $this->getMoney(),
        ];
    }

    protected function returnSuccess(string $msg = null, $result = null, bool $is_log = false){
        if($is_log===true) {
            $this->log(1, $msg, $result);
        }

        return [
            'OrderNo' => $this->getOrderNo(),
            'msg' => $msg,
            'result' => $result,
            'amt' => $this->getMoney(),
            'member_creditcard_id' => $this->getMemberCreditcardId(),
            'rec_trade_id' => is_array($result) && isset($result['rec_trade_id']) ? $result['rec_trade_id'] : null,
            'bank_transaction_id' => is_array($result) && isset($result['bank_transaction_id']) ? $result['bank_transaction_id'] : null,
        ];
    }

    private function log(int $pay_status, string $msg = null, $result = null, int $error_code = null) : void{
        $params = [
            'pay_type' => $this->pay_type,
            'task_id' => isset($this->task->id) ? $this->task->id : null,
            'pay_status' => $pay_status,
            'error_code' => (int) $error_code,
            'msg' => $msg,
            'obj' => json_encode([$result], JSON_UNESCAPED_UNICODE),
            'OrderNo' => $this->getOrderNo(),
            'amt' => $this->getMoney(),
            'member_creditcard_id' => $this->getMemberCreditcardId(),
            'rec_trade_id' => null,
            'bank_transaction_id' => is_array($result) && isset($result['bank_transaction_id']) ? $result['bank_transaction_id'] : null,
        ];

        try {
            $this->taskPayLogRepository->insertByParams($params);
        }catch (\Exception $e){
            Log::error('PaymentAbstract exception: ', [ 'params' => $params, $e->getMessage()]);
        }
    }

    protected function setOrderNo($is_random_serial = false){
        $OrderNo = $is_random_serial;
        if(isset($this->task->id)) {
            $TaskNo = TaskNo($this->task->id);
            if(is_bool($is_random_serial)) {
                $OrderNo = $is_random_serial === false ? $TaskNo : $TaskNo . '_' . rand(10, 99);
            }
        }
        $this->OrderNo = $OrderNo;

        return $OrderNo;
    }

    /**
     * @return null
     */
    public function getOrderNo()
    {
        return $this->OrderNo;
    }

    /**
     * @return null
     */
    public function getMemberCreditcardId()
    {
        return $this->member_creditcard_id;
    }

    /**
     * @param null $member_creditcard_id
     */
    public function setMemberCreditcardId($member_creditcard_id): void
    {
        $this->member_creditcard_id = $member_creditcard_id;
    }



}
