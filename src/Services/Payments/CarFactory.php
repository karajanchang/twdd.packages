<?php


namespace Twdd\Services\Payments;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Events\SpgatewayFailEvent;
use Twdd\Facades\TaskNo;
use Twdd\Models\DriverMerchant;
use Zhyu\Facades\ZhyuTool;

class CarFactory extends PaymentAbstract implements PaymentInterface
{
    use CarFactoryTrait;

    protected $pay_type = 4;

    public function back(int $amt, bool $is_notify_member = false){
        $this->preInit();

        if($this->checkIfDriverMerchantExists() === false){

            return $this->returnError( 2006, '智付通驗證錯誤 - 司機沒有啓用商店. 任務單號： ('.$this->task->id.')', null, true);
        }
        $carFactoryCreditCard = $this->getCarFactoryCreditCard();
        $this->setCarFactoryCreditcardId($carFactoryCreditCard->id);

        if($this->checkIfCarFactoryCreditcardExists() === false ){

            return $this->returnError( 2007, '智付通驗證錯誤 - 服務廠該張信用卡已移除或不存在. 任務單號： ('.$this->task->id.')', null, true);
        }

        $CloseType = $amt > 0  ?    1   :   2;
        $this->setMoney($amt);
        if($CloseType==2){
            $amt = ZhyuTool::plusMinusConvert($amt);
        }

        $OrderNo = isset($this->task->OrderNo) && strlen($this->task->OrderNo)>0   ?   $this->task->OrderNo   :  TaskNo::make($this->task->id);

        $datas = [
            'RespondType'         =>  'JSON',
            'Version'           =>  '1.0',
            'Amt'               =>  (int) $amt,
            'MerchantOrderNo'   =>  $OrderNo,
            'TimeStamp'         =>  time(),
            'IndexType'         =>  1,
            'CloseType'         =>  $CloseType,
        ];

        $key = env('APP_ENV'). 'SpagetwayPayTimestamp'.$this->task->id;
        try {
            $lock = Cache::lock(env('APP_ENV') . 'SpgatewayPayment' . $this->task->id, $this->seconds);
            Cache::put($key, time(), 30);
            if($lock->get()){
                $url = env('SPGATEWAY_BACK_URL', 'https://core.spgateway.com/API/CreditCard/Close');
                $res = $this->post($url, $this->preparePostData($datas));
                if (isset($res->Status) && $res->Status == 'SUCCESS') {
                    $msg = '刷卡成功 (單號：' . $this->task->id . ')';
                    Log::info($msg . ': ', [$res]);

                    return $this->returnSuccess($msg, $res, true);
                } else {
                    $msg = '刷卡失敗 (單號：' . $this->task->id . ')';
                    Log::info($msg . ': ', [$res]);
                    //$this->mail(new InfoAdminMail('［系統通知］智付通，刷卡失敗', $msg, $res));

                    if($is_notify_member===true) {
                        event(new SpgatewayFailEvent($this->task, $res));
                    }

                    return $this->returnError(2003, $msg, $res, true);
                }
            }

            $reverse_seconds =  $this->cacheReserveSeconds($key);
            $this->error->setReplaces('try_seconds', $reverse_seconds);

            return $this->returnError(2004, '刷卡付款，請過 '.$reverse_seconds.' 秒後再試', null, true);
        }catch (\Exception $e){
            $msg = '刷卡異常 (單號：'.$this->task->id.'): '.$e->getMessage();
            Log::info($msg, [$e]);
            Bugsnag::notifyException($e);
            //$this->mail(new InfoAdminMail('［系統通知］!!!智付通，刷卡異常!!!', $msg));

            if($is_notify_member===true) {
                event(new SpgatewayErrorEvent($this->task));
            }

            return $this->returnError( 2005, $msg, null, true);
        }

    }

    public function cancel(string $OrderNo = null, int $amount = null){
        if(is_null($OrderNo)){
            $OrderNo = $this->task->OrderNo;
            if(empty($OrderNo)) return false;
        }else{
            $driverMerchant = DriverMerchant::find(env('SPGATEWAY_BIND_DRIVER_MERCHANT_ID', 1443));
            $this->setDriverMerchant($driverMerchant);
            $this->setMoney($amount);
        }
        $this->setOrderNo($OrderNo);

        if($this->checkIfDriverMerchantExists() === false){

            return $this->returnError( 2006, '智付通驗證錯誤 - 司機沒有啓用商店. 刷卡單號： ('.$OrderNo.')', null, true);
        }

        try{
            $lock = Cache::lock(env('APP_ENV') . 'SpgatewayPayment' . $OrderNo, $this->seconds);
            $res = null;
            $key = env('APP_ENV'). 'SpagetwayCancelTimestamp'.$OrderNo;
            if($lock->get()) {
                Cache::put($key, time(), 30);
                $url = env('SPGATEWAY_CANCEL_URL', 'https://core.spgateway.com/API/CreditCard/Cancel');
                $datas = $this->prepareCancelPostData($OrderNo, $amount);
                $res = $this->post($url, $datas);

                if (isset($res->Status) && $res->Status == 'SUCCESS') {

                    return $this->returnSuccess('成功', $res, true);
                } else {

                    return $this->returnError(2008, '取消授權失敗，請稍後再試', $res, true);
                }
            }

            $reverse_seconds =  $this->cacheReserveSeconds($key);
            $this->error->setReplaces('try_seconds', $reverse_seconds);

            return $this->returnError(2004, '取消授權多次，請過'.$reverse_seconds.'秒後再試');
        }catch(\Exception $e){
            $msg = '取消授權異常 商店訂單編號(：'.$OrderNo.'): '.$e->getMessage();
            Log::info(__CLASS__.'::'.__METHOD__.' exception: ', [$msg, $e]);

            return $this->returnError(3004, '操作失敗，請稍後再試', $res, true);
        }
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