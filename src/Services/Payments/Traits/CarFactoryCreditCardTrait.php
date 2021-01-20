<?php


namespace Twdd\Services\Payments\Traits;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Events\SpgatewayFailEvent;
use Zhyu\Errors\CurlTimeout;

trait CarFactoryCreditCardTrait
{
    use CommonTrait;


    function firePay(array $datas, bool $is_notify_member = true){
        $key = env('APP_ENV'). 'SpagetwayPayTimestamp'.$this->task->id;
        Log::info(__CLASS__.'::'.__METHOD__.': ', $datas);

        try{
            $lock = Cache::lock(env('APP_ENV') . 'SpgatewayPayment' . $this->task->id, $this->seconds);
            Cache::put($key, time(), 30);
            if($lock->get()) {
                $url = env('SPGATEWAY_URL', 'https://core.spgateway.com/API/CreditCard');
                try {
                    $res = $this->post($url, $this->preparePostData($datas));
                }catch(CurlTimeout $e){
                    $msg = '車廠刷卡timeout (單號：' . $this->task->id . ')';

                    return $this->notifyExceptionAndLog($e, 2005, $msg, $is_notify_member, true);
                }

                if (isset($res->Status) && $res->Status == 'SUCCESS') {
                    $msg = '車廠刷卡成功 (單號：' . $this->task->id . ')';
                    Log::info($msg . ': ', [$res]);

                    return $this->returnSuccess($msg, $res, true);
                } else {
                    $msg = '車廠刷卡失敗 (單號：' . $this->task->id . ')';
                    Log::info($msg . ': ', [$res]);
                    //$this->mail(new InfoAdminMail('［系統通知］智付通，刷卡失敗', $msg, $res));

                    if($is_notify_member===true) {
                        event(new SpgatewayFailEvent($this->task, $res));
                    }
                    //車廠不用去記刷卡失敗
                    //app(SpgatewayErrorDectect::class)->init($this->carFactoryCreditCard, $res->Status, $res->Message);

                    return $this->returnError(2003, $msg, $res, true);
                }
            }
            $reverse_seconds =  $this->cacheReserveSeconds($key);
            $this->error->setReplaces('try_seconds', $reverse_seconds);

            return $this->returnError(2004, '車廠刷卡付款，請過 '.$reverse_seconds.' 秒後再試', null, true);
        }catch(\Exception $e){
            $msg = '車廠刷卡異常 (單號：'.$this->task->id.'): '.$e->getMessage();

            //$this->mail(new InfoAdminMail('［系統通知］!!!智付通，刷卡異常!!!', $msg));
            return $this->notifyExceptionAndLog($e, 2005, $msg, $is_notify_member);
        }
    }
}