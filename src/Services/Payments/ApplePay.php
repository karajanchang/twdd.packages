<?php


namespace Twdd\Services\Payments;


use Illuminate\Support\Facades\Log;
use Twdd\Events\ApplePayFailEvent;
use Twdd\Repositories\MemberPayTokenRepository;
use Twdd\Repositories\TaskPayLogRepository;
use Twdd\Services\Payments\TapPlay\TapPlayTrait;
use Zhyu\Facades\ZhyuTool;

class ApplePay extends PaymentAbstract implements PaymentInterface
{
    use TapPlayTrait;

    protected $pay_type = 5;
    private $merchant_id = null;

    const post_url = 'https://prod.tappaysdk.com/tpc/payment/pay-by-prime';
    const post_url_sandbox = 'https://sandbox.tappaysdk.com/tpc/payment/pay-by-prime';
    const post_refund_url = 'https://prod.tappaysdk.com/tpc/transaction/refund';
    const post_refund_url_sandbox = 'https://sandbox.tappaysdk.com/tpc/transaction/refund';


    public function back(int $amt, bool $is_notify_member = false){
        $this->setMoney($amt);
        $msg = 'apple pay退回成功 (單號：' . $this->task->id . ')';


        $params = $this->initBackParams($this->task);

        try {
            $default_url = env('APP_TYPE', 'development') !='production' ? self::post_refund_url_sandbox : self::post_refund_url;
            $url = env('APPLEPAY_REFUND_URL', $default_url);
            $res = $this->post($url, $params);

            if (isset($res['status']) && $res['status'] > 0) {
                $msg = 'ApplePay退款失敗 (單號：' . $this->task->id . ')';
                Log::info($msg, ['params' => $params, 'res' => $res]);

                if($is_notify_member===true) {
                    event(new ApplePayFailEvent($this->task, $res));
                }

                return $this->returnError(4004, $msg, $res, true);
            }
            Log::info(__CLASS__.'::'.__METHOD__.': ', ['params' => $params, 'res' => $res]);

            return $this->returnSuccess($msg, $res, true);
        }catch (\Exception $e){
            $msg = 'ApplePay退款timeout (單號：' . $this->task->id . '): '.$e->getMessage();
            Log::error(__CLASS__.'::'.__METHOD__.' exception: ', [$e]);

            return $this->returnError(500, $msg, $e, true);
        }
    }

    public function cancel(string $OrderNo = null, int $amount = null){

    }

    /*
     * 取得退款params
     */
    private function initBackParams() : array{
        //---1.從task_pay_logs去抓 rec_trade_id
        $taskPayLog = app(TaskPayLogRepository::class)->findByTaskId($this->task->id);
        $money = (int) $this->getMoney();
        if($money < 0) {
            $amount = ZhyuTool::plusMinusConvert($money);
        }
        return [
            'partner_key' => $this->partner_key,
            'rec_trade_id' => isset($taskPayLog->rec_trade_id) && !empty($taskPayLog->rec_trade_id) ? $taskPayLog->rec_trade_id : null,
            'amount' => $amount,
        ];
    }

    private function initParams(array $params): array{
        if(!isset($this->task->member)) return [];

        $member = $this->task->member;

        $memberPayToken = app(MemberPayTokenRepository::class)->lastByMemberIdAndPayType($member->id, $this->pay_type);
        $is_random_serial = isset($params['is_random_serial']) ? $params['is_random_serial'] : false;
        $OrderNo = $this->setOrderNo($is_random_serial);
        $cardholder = [
            'phone_number' => $member->UserPhone,
            'name' => (string) $member->UserName,
            'email' => (string) $member->UserEmail,
        ];
        $res = $this->createMerchants([$this->task->driver_id]);
        sleep(3);
        $merchant_id = 0;
        if($res===false){
            Log::info(__CLASS__.'::'.__METHOD__.' ...ApplePay Merchant失敗');
        }else {
            $merchants = $res['merchants'];
            $merchantArray = $merchants[0];
            $merchant_id = $merchantArray['merchant_id'];
            Log::info(__CLASS__ . '::' . __METHOD__ . ': ', $merchantArray);
        }


        return [
            'prime' => $memberPayToken->token,
            'order_number' => $OrderNo,
            'partner_key' => $this->partner_key,
            'merchant_id' => $merchant_id,
            'details' => $this->details,
            'amount' => $this->getMoney(),
            'cardholder' => $cardholder,
            'remember' => true,
        ];
    }

    public function pay(array $params = [], bool $is_notify_member = true){
        $msg = 'ApplePay付款成功 (單號：' . $this->task->id . ')';

        $params = $this->initParams($params);

        try {
            if(empty($params['merchant_id'])){
                $msg = '無法取得merchant';
                Log::info($msg, [$params]);

                return $this->returnError(500, $msg, null, true);
            }

            $default_url = env('APP_TYPE', 'development') =='production' ? self::post_url : self::post_url_sandbox;
            $url = env('APPLEPAY_URL', $default_url);
            $res = $this->post($url, $params);

            if (isset($res['status']) && $res['status'] > 0) {
                $msg = 'ApplePay付款失敗 (單號：' . $this->task->id . ')';
                Log::info($msg, ['params' => $params, 'res' => $res]);

                if($is_notify_member===true) {
                    event(new ApplePayFailEvent($this->task, $res));
                }

                return $this->returnError(4004, $msg, $res, true);
            }
            Log::info(__CLASS__.'::'.__METHOD__.': ', ['params' => $params, 'res' => $res]);

            return $this->returnSuccess($msg, $res, true);
        }catch (\Exception $e){
            $msg = 'ApplePay付款timeout (單號：' . $this->task->id . '): '.$e->getMessage();
            Log::error(__CLASS__.'::'.__METHOD__.' exception: ', [$e]);

            return $this->returnError(500, $msg, $e, true);
        }
    }



    public function query(){

    }
}