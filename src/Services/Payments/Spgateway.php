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
use Zhyu\Facades\ZhyuTool;
use TaskNo;
use \Zhyu\Errors\CurlTimeout;
use \Zhyu\Errors\CurlError;

class Spgateway extends PaymentAbstract implements PaymentInterface
{
    private $driverMerchant;
    private $memberCreditCard;
    private $seconds = 30;

    public function back(int $amt, bool $is_notify_member = false){
        $this->preInit();

        if($this->checkIfDriverMerchantExists() === false){

            return $this->returnError( 2006, '智付通驗證錯誤 - 司機沒有啓用商店. 任務單號： ('.$this->task->id.')', null, true);
        }

        $memberCreditCard = $this->getMemberCreditCard();
        $this->setMemberCreditcardId($memberCreditCard->id);

        if($this->checkIfMemberCreditcardExists() === false){

            return $this->returnError( 2007, '智付通驗證錯誤 - 會員該張信用卡已移除或不存在. 任務單號： ('.$this->task->id.')', null, true);
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
            $cache_timestamp = Cache::get($key);
            $seconds = empty($cache_timestamp) ? 1 : 30 - (time() - $cache_timestamp);
            $this->error->setReplaces('try_seconds', $seconds);

            return $this->returnError(2004, '刷卡付款，請過 '.$seconds.' 秒後再試', null, true);
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

    public function cancel(){

    }

    private function checkIfDriverMerchantExists(){
        if(empty($this->driverMerchant->MerchantID) || empty($this->driverMerchant->MerchantHashKey) || empty($this->driverMerchant->MerchantIvKey)){

            return false;
        }

        return true;
    }

    private function checkIfMemberCreditcardExists(){
        $memberCreditCard = $this->getMemberCreditCard();
        if(empty($memberCreditCard->id)){

            return false;
        }

        return true;
    }

    public function pay(array $params = [], bool $is_notify_member = true){
        $this->preInit();

        if($this->checkIfDriverMerchantExists() === false){

            return $this->returnError( 2006, '智付通驗證錯誤 - 司機沒有啓用商店. 任務單號： ('.$this->task->id.')', null, true);
        }

        $memberCreditCard = $this->getMemberCreditCard();
        $this->setMemberCreditcardId($memberCreditCard->id);

        if($this->checkIfMemberCreditcardExists() === false){

            return $this->returnError( 2007, '智付通驗證錯誤 - 會員該張信用卡已移除或不存在. 任務單號： ('.$this->task->id.')', null, true);
        }

        $payer_email = isset($params['payer_email']) ? $params['payer_email'] : $memberCreditCard->CardHolder;
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
            'TokenValue'        =>  $this->memberCreditCard->TokenValue,
            'TokenTerm'         =>  $this->task->member_id,
            'TokenSwitch'       =>  'on',
        ];
        $msg = '刷卡資料 (單號：' . $this->task->id . ')';
        Log::info($msg, $datas);

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

                    app(SpgatewayErrorDectect::class)->init($memberCreditCard, $res->Status, $res->Message);

                    return $this->returnError(2003, $msg, $res, true);
                }
            }
            $cache_timestamp = Cache::get($key);
            $seconds = empty($cache_timestamp) ? 1 : 30 - (time() - $cache_timestamp);
            $this->error->setReplaces('try_seconds', $seconds);

            return $this->returnError(2004, '刷卡付款，請過 '.$seconds.' 秒後再試', null, true);
        }catch(\Exception $e){
            $msg = '刷卡異常 (單號：'.$this->task->id.'): '.$e->getMessage();

            //$this->mail(new InfoAdminMail('［系統通知］!!!智付通，刷卡異常!!!', $msg));
            return $this->notifyExceptionAndLog($e, 2005, $msg, $is_notify_member);
        }
    }

    private function notifyExceptionAndLog($e, int $code, string $msg = '', bool $is_notify_member = false, bool $is_payment_timeout = false){
        Log::info($msg, [$e]);
        Bugsnag::notifyException($e);

        if($is_notify_member===true) {
            event(new SpgatewayErrorEvent($this->task));
        }

        return $this->returnError( 2005, $msg, null, true, $is_payment_timeout);
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
        $this->preInit();
        $memberCreditCard = $this->getMemberCreditCard();
        $this->setMemberCreditcardId($memberCreditCard->id);
        if (isset($this->task->id) && isset($this->task->pay_type) && $this->task->id > 0 && $this->task->pay_type == 2) {
            $key = env('APP_ENV') . 'SpagetwayQueryTimestamp' . $this->task->id;
            try {
                $lock = Cache::lock(env('APP_ENV') . 'SpgatewayPayment' . $this->task->id, $this->seconds);
                if ($lock->get()) {
                    Cache::put($key, time(), 30);
                    $url = env('SPGATEWAY_QUERY_URL', 'https://core.spgateway.com/API/QueryTradeInfo');

                    $MerchantOrderNo = isset($task->OrderNo) && strlen($this->task->OrderNo)>0 ? $this->task->OrderNo : TaskNo::make($this->task->id);

                    $datas = [
                        'MerchantID' => $this->driverMerchant->MerchantID,
                        'MerchantOrderNo' => $MerchantOrderNo,
                        'Amt' => $this->task->TaskFee,
                    ];
                    $res = $this->post($url, $this->preparePostDataQuery($datas));
                    Log::info('Spgateway Query: ', [$res]);

                    if (isset($res->Status) && $res->Status == 'SUCCESS') {
                        $msg = '智付通查詢: 狀態成功';

                        return $this->returnSuccess($msg, $res);
                    }else{
                        $msg = '智付通查詢: 狀態失敗';

                        return $this->returnSuccess($msg, $res);
                    }
                }

                $cache_timestamp = Cache::get($key);
                $seconds = empty($cache_timestamp) ? 1 : 30 - (time() - $cache_timestamp);

                $this->error->setReplaces('try_seconds', $seconds);
                return $this->returnError(3001, '查詢智付通，請過 ' . $seconds . ' 秒後再試');
            } catch (\Exception $e) {
                $msg = '查詢智付通異常 (單號：' . $this->task->id . '): ' . $e->getMessage();
                Log::info($msg, [$e]);
                Bugsnag::notifyException($e);

                return $this->returnError(3002, $msg);
            }
        }

        return $this->returnError(3003, '此單非信用卡付款，無法查詢');
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

    private function preparePostDataQuery(array $postData){
        ksort($postData);
        $check_str = http_build_query($postData);
        $CheckCodeStr = "IV=".$this->driverMerchant->MerchantIvKey.'&'.$check_str."&Key=".$this->driverMerchant->MerchantHashKey;
        $CheckValue = strtoupper(hash("sha256", $CheckCodeStr));
        $postData['Version'] = '1.1';
        $postData['RespondType'] = 'JSON';
        $postData['TimeStamp'] = time();
        $postData['CheckValue'] = $CheckValue;

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

}