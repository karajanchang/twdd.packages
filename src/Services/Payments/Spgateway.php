<?php


namespace Twdd\Services\Payments;


use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Events\SpgatewayFailEvent;
use Twdd\Repositories\DriverMerchantRepository;
use Twdd\Repositories\MemberCreditcardRepository;
use Zhyu\Facades\ZhyuCurl;

class Spgateway extends PaymentAbstract implements PaymentInterface
{
    private $driverMerchant;
    private $memberCreditCard;
    private $seconds = 30;

    public function back(){

    }

    public function cancel(){

    }

    public function pay(array $params = []){
        $this->preInit();
        $memberCreditCard = $this->getMemberCreditCard();
        $this->setMemberCreditcardId($memberCreditCard->id);
        $payer_email = isset($params['payer_email']) ? $params['payer_email'] : $memberCreditCard->CardHolder;
        $is_random_serial = isset($params['is_random_serial']) ? $params['is_random_serial'] : false;
        $OrderNo = $this->setOrderNo($is_random_serial);

        $money = $this->getMoney();

        if(strlen($payer_email)==0){

            return $this->returnError( 2001, $money, '驗證錯誤 - 沒有email');
        }


        if((int) $money<=0){
            Log::info('刷卡0元，成功 (單號：'. $this->task->id. ')');

            return $this->returnSuccess('結帳成功');
        }

        $datas = [
            'TimeStamp'         =>  time(),
            'Version'           =>  '1.0',
            'MerchantOrderNo'   =>  $OrderNo,
            'Amt'               =>  $money,
            'ProdDesc'          =>  '代駕費用',
            'PayerEmail'        =>  $payer_email,
            'TokenValue'        =>  $this->memberCreditCard->TokenValue,
            'TokenTerm'         =>  $this->task->member_id,
            'TokenSwitch'       =>  'on',
        ];

        try{
            $lock = Cache::lock(env('APP_ENV') . 'SpgatewayPayment' . $this->task->id, $this->seconds);
            $key = env('APP_ENV'). 'SpagetwayTimestamp';
            Cache::put($key, time());
            if($lock->get()) {
                $url = env('SPGATEWAY_URL');
                $res = $this->post($url, $this->preparePostData($datas));
                if (isset($res->Status) && $res->Status == 'SUCCESS') {
                    $msg = '刷卡成功 (單號：' . $this->task->id . ')';
                    Log::info($msg . ': ', [$res]);

                    return $this->returnSuccess($msg, $res);
                } else {
                    $msg = '刷卡失敗 (單號：' . $this->task->id . ')';
                    Log::info($msg . ': ', [$res]);
                    //$this->mail(new InfoAdminMail('［系統通知］智付通，刷卡失敗', $msg, $res));

                    event(new SpgatewayFailEvent($this->task, $res));

                    return $this->returnError(2003, $msg, $res);
                }
            }
            $seconds = 30 - time() - (int) Cache::get($key);
            return $this->error('刷卡付款，請過 '.$seconds.' 秒後再試');
        }catch(\Exception $e){
            $msg = '刷卡異常 (單號：'.$this->task->id.'): '.$e->getMessage();
            Log::info($msg);
            Bugsnag::notifyException($e);
            //$this->mail(new InfoAdminMail('［系統通知］!!!智付通，刷卡異常!!!', $msg));

            event(new SpgatewayErrorEvent($this->task));

            return $this->returnError( 500, $msg);
        }
    }

    /*
    private function mail(InfoAdminMail $infoAdminMail){
        $emails = explode(',', env('ADMIN_NOTIFY_EMAilS', 'service@twdd.com.tw'));
        if(count($emails)) {
            Mail::to($emails)->queue($infoAdminMail);
        }
    }
    */

    public function query(){

    }


    public function getDriverMerchant(){

        return $this->driverMerchant;
    }

    public function getMemberCreditCard(){

        return $this->memberCreditCard;
    }

    private function preInit(){
        $this->driverMerchant = app(DriverMerchantRepository::class)->findByTaskId($this->task);
        $this->memberCreditCard = app(MemberCreditcardRepository::class)->findByTaskId($this->task);
    }



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

            throw new \Exception('Please set SPGATEWAY_URL value in .env');
        }
        $res = ZhyuCurl::url($url)->post($postData);

        return json_decode($res);
    }

}