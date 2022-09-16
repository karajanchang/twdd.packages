<?php


namespace Twdd\Services\Payments;

use Illuminate\Support\Facades\Log;
use Twdd\Errors\PaymentErrors;
use Twdd\Models\DriverMerchant;
use Twdd\Repositories\TaskPayLogRepository;
use Twdd\Services\Payments\SpgatewayErrorDectect;
use Twdd\Repositories\MemberCreditcardRepository;
use Twdd\Services\Payments\Traits\SpgatewayTrait;
use Twdd\Services\Payment_v2\SpGateway\SpGatewayService;

class BlackHat extends PaymentAbstract implements PaymentInterface
{
    use SpgatewayTrait;

    protected $pay_type = 2;

    public function pay(array $params = [], bool $notifyMember = true)
    {
        $driverMerchant = $this->getCompanyMerchant();

        $orderNo = "";
        $proPaySuffix = "";

        // 刷訂金
        if ($this->calldriverTaskMap) {
            $member = $this->calldriverTaskMap->member;
            $orderNo = 'bh_' . str_pad($this->calldriverTaskMap->id, 8, "0", STR_PAD_LEFT);
            $money = $params['money'];
            $proPaySuffix = "訂金";

            $this->setMoney($money);
            $this->setOrderNo($orderNo);
        }

        // 刷尾款
        if ($this->task)
        {
            $member = $this->task->member;
            $orderNo = 'bh_' . str_pad($this->task->id, 8, "0", STR_PAD_LEFT);
            $blackHatDetail = $this->task->calldriver_task_map->blackhat_detail;
            $money = $this->task->TaskFee - $blackHatDetail->deposit;
            $proPaySuffix = "任務金";
            $this->setMoney($money);
            $this->setOrderNo($orderNo);
            if (isset($params['is_random_serial']) && $params['is_random_serial'] === true) {
                $orderNo = $this->getOrderNo();
            }
        }

        $memberCreditCard = app(MemberCreditcardRepository::class)->defaultCreditCard($member->id);

        try {

            $payment = new SpGatewayService();
            $payment->setOrderNo($orderNo);
            $payment->setProDesc('黑帽客' . $proPaySuffix);
            $res = $payment->pay($money, $memberCreditCard, $driverMerchant);

            if (isset($res['Status']) && $res['Status'] === 'SUCCESS') {

                return $this->returnSuccess('刷卡成功', $res, true);

            } else if (isset($res['Message']) && $res['Message']) {

                app(SpgatewayErrorDectect::class)->init($memberCreditCard, $res['Status'], $res['Message']);

                return $this->returnError(2003, '刷卡失敗', $res, true);

            } else {

                return $this->returnError(2003, '刷卡失敗', ($res) ? $res : null, true);
            }

        } catch (\Exception $e) {
            $msg = '';
            if ($this->calldriverTaskMap) {
                $msg = '刷卡異常 (預約單號：'.$this->calldriverTaskMap->id.'): '.$e->getMessage();
            }
            if ($this->task) {
                $msg = '刷卡異常 (任務單號：'.$this->task->id.'): '.$e->getMessage();
            }

            return $this->notifyExceptionAndLog($e, 2005, $msg, 0);
        }
    }

    public function cancel(string $OrderNo = null, int $amount = null)
    {
        $driverMerchant = $this->getCompanyMerchant();

        $money = 0;
        $orderNo = "";
        // 刷訂金
        if ($this->calldriverTaskMap) {
            $payLogs = $this->calldriverTaskMap->payLogs->where('pay_status', 1);
            $payLog = $payLogs[0] ?? null;

            $money = $payLog->amt;
            $orderNo = $payLog->OrderNo;
        }

        try {

            $payment = new SpGatewayService();
            $res = $payment->cancel($money, $orderNo, $driverMerchant);

            if (isset($res['Status']) && $res['Status'] === 'SUCCESS') {

                return $this->returnSuccess('取消授權成功', $res, true);

            } else if (isset($res['Message']) && $res['Message']) {

                return $this->returnError(2003, '取消授權失敗', $res, true);

            } else {

                return $this->returnError(2003, '取消授權失敗', ($res) ? $res : null, true);
            }

        } catch(\Exception $e) {

            $msg = '取消授權異常 商店訂單編號(：'.$orderNo.'): '.$e->getMessage();
            Log::info(__CLASS__.'::'.__METHOD__.' exception: ', [$msg, $e]);

            return $this->returnError(3004, '操作失敗，請稍後再試', $res, true);
        }
    }

    public function back(int $amt, bool $is_notify_member = false)
    {
        $driverMerchant = $this->getCompanyMerchant();

        $money = 0;
        $orderNo = "";
        // 刷訂金
        if ($this->calldriverTaskMap) {
            $payLogs = $this->calldriverTaskMap->payLogs->where('pay_status', 1);
            $payLog = $payLogs[0] ?? null;

            $money = $payLog->amt;
            $orderNo = $payLog->OrderNo;
        }

        try {

            $payment = new SpGatewayService();
            $res = $payment->back($money, $orderNo, 2, $driverMerchant);

            if (isset($res['Status']) && $res['Status'] === 'SUCCESS') {

                return $this->returnSuccess('退刷成功', $res, true);

            } else if (isset($res['Message']) && $res['Message']) {

                return $this->returnError(2003, '退刷失敗', $res, true);

            } else {

                return $this->returnError(2003, '退刷失敗', ($res) ? $res : null, true);
            }

        } catch(\Exception $e) {

            $msg = '退刷異常 (單號：'.$this->calldriverTaskMap->id.'): '.$e->getMessage();
            Log::info(__CLASS__.'::'.__METHOD__.' exception: ', [$msg, $e]);

            return $this->returnError(3004, '操作失敗，請稍後再試', $res, true);
        }
    }

    public function query()
    {
        $driverMerchant = $this->getCompanyMerchant();
        $money = 0;
        $orderNo = "";
        // 刷訂金
        if ($this->calldriverTaskMap) {
            $payLogs = $this->calldriverTaskMap->payLogs->where('pay_status', 1);
            $payLog = $payLogs[0] ?? null;

            $money = $payLog->amt;
            $orderNo = $payLog->OrderNo;

            $this->setMoney($money);
            $this->setOrderNo($orderNo);
        }

        try {

            $payment = new SpGatewayService();
            $res = $payment->query($money, $orderNo, $driverMerchant);

            if (isset($res['Status']) && $res['Status'] === 'SUCCESS') {

                return $this->returnSuccess('智付通查詢: 狀態成功', $res);

            } else if (isset($res['Message']) && $res['Message']) {

                return $this->returnError(3002, '智付通查詢失敗', $res);

            } else {

                return $this->returnError(3002, '智付通查詢失敗', ($res) ? $res : null);
            }


        } catch (\Exception $e) {

            $msg = '查詢智付通異常 (單號：' . $this->calldriverTaskMap->id . '): ' . $e->getMessage();
            Log::info($msg, [$e]);
            Bugsnag::notifyException($e);

            return $this->returnError(3002, $msg);
        }
    }

    private function getCompanyMerchant()
    {
        $driverMerchant = new DriverMerchant();

        $driverMerchant->MerchantID = 'TWD161038650';
        $driverMerchant->MerchantHashKey = 'U5XsUQLg0bvYAprhXm8FybhHZzDiS9cw';
        $driverMerchant->MerchantIvKey = 'Cog226xrtyu4nvtP';

        return $driverMerchant;
    }
}
