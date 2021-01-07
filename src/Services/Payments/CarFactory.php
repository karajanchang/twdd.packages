<?php


namespace Twdd\Services\Payments;


use Illuminate\Support\Facades\Log;

class CarFactory extends PaymentAbstract implements PaymentInterface
{
    use CarFactoryTrait;

    protected $pay_type = 4;

    public function back(int $amt, bool $is_notify_member = false){
        $this->setMoney($amt);
        $msg = '付現退回成功 (單號：' . $this->task->id . ')';

        return $this->returnSuccess($msg, null, true);
    }

    public function cancel(string $OrderNo = null, int $amount = null){

    }

    public function pay(array $params = [], bool $is_notify_member = true){
        if($this->task->car_factory_pay_type==1) {
            $msg = '現金付款成功 (單號：' . $this->task->id . ')';

            return $this->returnSuccess($msg, null, true);
        }

        $this->preInit();

        if($this->checkIfDriverMerchantExists() === false){

            return $this->returnError( 2006, '智付通驗證錯誤 - 司機沒有啓用商店. 任務單號： ('.$this->task->id.')', null, true);
        }
        $carFactoryCreditCard = $this->getCarFactoryCreditCard();
        $this->setCarFactoryCreditcardId($carFactoryCreditCard->id);

        if($this->checkIfCarFactoryCreditcardExists() === false ){

            return $this->returnError( 2007, '智付通驗證錯誤 - 服務廠該張信用卡已移除或不存在. 任務單號： ('.$this->task->id.')', null, true);
        }

        $payer_email = $this->carFactoryCreditCard->PayerEmail;
        $is_random_serial = isset($params['is_random_serial']) ? $params['is_random_serial'] : false;
        $OrderNo = $this->setOrderNo($is_random_serial);

        $money = $this->getMoney();

        if(strlen($payer_email)==0){

            return $this->returnError( 2001, '驗證錯誤 - 沒有email', null, true);
        }

        if((int) $money<=0){
            Log::info('刷卡0元，成功 (單號：'. $this->task->id. ')');

            return $this->returnSuccess('結帳成功', null, true);
        }

        $datas = [
            'TimeStamp'         =>  time(),
            'Version'           =>  '1.0',
            'MerchantOrderNo'   =>  $OrderNo,
            'Amt'               =>  $money,
            'ProdDesc'          =>  '代駕費用',
            'PayerEmail'        =>  $payer_email,
            'TokenValue'        =>  $this->carFactoryCreditCard->TokenValue,
            'TokenTerm'         =>  $this->task->car_factory_id,
            'TokenSwitch'       =>  'on',
        ];
        $msg = '刷卡資料 (單號：' . $this->task->id . ')';
        Log::info($msg, $datas);


        $msg = '信用卡付款成功 (單號：' . $this->task->id . ')';
        return $this->returnSuccess($msg, null, true);
    }

    public function query(){

    }
}