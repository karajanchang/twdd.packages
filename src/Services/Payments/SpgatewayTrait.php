<?php


namespace Twdd\Services\Payments;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Models\DriverMerchant;
use Twdd\Models\MemberCreditcard;
use Twdd\Repositories\DriverMerchantRepository;
use Twdd\Repositories\MemberCreditcardRepository;
use Zhyu\Facades\ZhyuCurl;

Trait SpgatewayTrait
{
    private $driverMerchant;
    private $memberCreditCard;
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

    protected function preInit(){
        $this->driverMerchant = app(DriverMerchantRepository::class)->findByTaskId($this->task);
        $this->memberCreditCard = app(MemberCreditcardRepository::class)->findByTaskId($this->task);
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

    /**
     * @return mixed
     */
    public function getMemberCreditCard()
    {
        return $this->memberCreditCard;
    }

    /**
     * @param mixed $memberCreditCard
     */
    public function setMemberCreditCard(Model $memberCreditCard)
    {
        $this->memberCreditCard = $memberCreditCard;

        return $this;
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
        Bugsnag::notifyException($e);

        if($is_notify_member===true) {
            event(new SpgatewayErrorEvent($this->task));
        }

        return $this->returnError( 2005, $msg, null, true, $is_payment_timeout);
    }
}
