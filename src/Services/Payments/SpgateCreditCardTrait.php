<?php


namespace Twdd\Services\Payments;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Events\SpgatewayFailEvent;
use Zhyu\Errors\CurlTimeout;
use Zhyu\Facades\ZhyuCurl;

trait SpgateCreditCardTrait
{
    private $driverMerchant;
    private $seconds = 30;

    private function preparePostData(array $datas){
        $post_data_str = http_build_query($datas);
        $encrypt_data = $this->spgateway_encrypt($post_data_str);

        $postData = [
            'MerchantID_'   =>  $this->driverMerchant->MerchantID,
            'Pos_'   =>  'JSON',
            'PostData_' =>  $encrypt_data,
        ];

        return $postData;
    }

    private function spgateway_encrypt($str = "") {
        $str = trim(bin2hex( openssl_encrypt($this->addPadding($str), 'aes-256-cbc', $this->driverMerchant->MerchantHashKey, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $this->driverMerchant->MerchantIvKey) ));

        return $str;
    }

    function addPadding($string, $blocksize = 32) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);

        return $string;
    }

    function post(string $url, array $postData){
        if(strlen($url)==0){

            throw new \Exception('Please set SPGATEWAY_XXX_URL value in .env');
        }
        $res = ZhyuCurl::url($url)->post($postData, (int)env('SPGATEWAY_TIMEOUT', 50));

        return json_decode($res);
    }

    function firePay(array $datas, bool $is_notify_member = true){
        $key = env('APP_ENV'). 'SpagetwayPayTimestamp'.$this->task->id;

        try{
            $lock = Cache::lock(env('APP_ENV') . 'SpgatewayPayment' . $this->task->id, $this->seconds);
            Cache::put($key, time(), 30);
            if($lock->get()) {
                $url = env('SPGATEWAY_URL', 'https://core.spgateway.com/API/CreditCard');
                try {
                    $res = $this->post($url, $this->preparePostData($datas));
                }catch(CurlTimeout $e){
                    $msg = '刷卡timeout (單號：' . $this->task->id . ')';

                    return $this->notifyExceptionAndLog($e, 2005, $msg, $is_notify_member, true);
                }

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

                    app(SpgatewayErrorDectect::class)->init($this->memberCreditCard, $res->Status, $res->Message);

                    return $this->returnError(2003, $msg, $res, true);
                }
            }
            $reverse_seconds =  $this->cacheReserveSeconds($key);
            $this->error->setReplaces('try_seconds', $reverse_seconds);

            return $this->returnError(2004, '刷卡付款，請過 '.$reverse_seconds.' 秒後再試', null, true);
        }catch(\Exception $e){
            $msg = '刷卡異常 (單號：'.$this->task->id.'): '.$e->getMessage();

            //$this->mail(new InfoAdminMail('［系統通知］!!!智付通，刷卡異常!!!', $msg));
            return $this->notifyExceptionAndLog($e, 2005, $msg, $is_notify_member);
        }
    }

    private function checkIfDriverMerchantExists(){
        if(empty($this->driverMerchant->MerchantID) || empty($this->driverMerchant->MerchantHashKey) || empty($this->driverMerchant->MerchantIvKey)){

            return false;
        }

        return true;
    }


    /**
     * @return mixed
     */
    public function getDriverMerchant()
    {
        return $this->driverMerchant;
    }

    /**
     * @param mixed $driverMerchant
     */
    public function setDriverMerchant(Model $driverMerchant)
    {
        $this->driverMerchant = $driverMerchant;

        return $this;
    }

    /*
     * 剩餘的秒數
     */
    private function cacheReserveSeconds(string $key) : int{
        $cache_timestamp = Cache::has($key) ? (int) Cache::get($key) : null;
        $reserve_seconds = is_null($cache_timestamp) ? 1 : 30 - (time() - $cache_timestamp);

        return $reserve_seconds;
    }

    /*
     * 通知錯誤及記錄日誌
     */
    private function notifyExceptionAndLog($e, int $code, string $msg = '', bool $is_notify_member = false, bool $is_payment_timeout = false){
        Log::info($msg, [$e]);
        try {
            \Bugsnag\BugsnagLaravel\Facades\Bugsnag::notifyException($e);
        }catch (\Exception $e){

        }

        if($is_notify_member===true) {
            event(new SpgatewayErrorEvent($this->task));
        }

        return $this->returnError( 2005, $msg, null, true, $is_payment_timeout);
    }
}