<?php

namespace Twdd\Services\Payment_v2\SpGateway;


use Carbon\Carbon;
use Twdd\Models\DriverMerchant;
use Twdd\Models\MemberCreditcard;
use Twdd\Services\Payment_v2\PaymentInterface;


class SpGatewayService
{
    private $orderNo;
    private $proDesc;
    private $payerEmail;

    public function createStore(array $postData)
    {
        $service = new CreateStore();
        return $service->createStore($postData);
    }

    public function bind(array $postData)
    {
        $service = new Bind();
        return $service->bind($postData);
    }

    public function pay(int $money, MemberCreditcard $creditCard, DriverMerchant $merchant)
    {
        $service = new Pay();
        if (empty($this->orderNo)) {
            $orderNo = $this->createOrderNo();
            $this->setOrderNo($orderNo);
        }
        $this->checkMoney($money);
        $this->checkMerchant($merchant);
        $this->checkCreditCard($creditCard);
        $this->checkOrderNo($this->orderNo);
        $this->checkDesc($this->proDesc);

        $data = [
            'MerchantOrderNo' => $orderNo,
            'PayerEmail' => $this->payerEmail,
            'TokenValue' => 'ca567c81c7eb42fd4ea53f7ae069d9a2c069dc44dda1d85fbba98e75e1d511c3',
            'TokenTerm' => 1,
            'Amt' => $money,
            'ProdDesc' => $this->proDesc,
        ];

        return $service->pay($data, $merchant->MerchantID, $merchant->MerchantHashKey, $merchant->MerchantIvKey);
    }

    public function setPayerEmail(string $email)
    {
        $this->payerEmail = $email;
    }

    public function setOrderNo(string $orderNo)
    {
        $this->orderNo = $orderNo;
    }

    public function setProDesc(string $proDesc)
    {
        $this->proDesc = $proDesc;
    }

    private function createOrderNo()
    {
        return 'TWDD' . Carbon::now()->format('YmdHis') . random_int(10, 99);
    }

    private function checkMoney($money)
    {
        if ($money <= 0) {
            throw new \Exception('刷卡金額設定必須要大於0');
        }
    }

    private function checkMerchant($merchant)
    {
        if (empty($merchant)
            || !isset($merchant->MerchantID)
            || !isset($merchant->MerchantHashKey)
            || !isset($merchant->MerchantIvKey)) {
            throw new \Exception('商店資料不齊全');
        }
    }

    private function checkPayFrom($payFrom)
    {
        if (!isset($payFrom->UserEmail) || empty($payFrom->UserEmail)) {
            throw new \Exception('付款者無設定E-mail');
        }
    }

    private function checkCreditCard($creditCard)
    {
        if (empty($creditCard)) {
            throw new \Exception('此信用卡不存在');
        }
        if (!isset($creditCard->TokenValue) || empty($creditCard->TokenValue)) {
            throw new \Exception('信用卡資料不齊全');
        }
    }

    private function checkOrderNo(string $orderNo)
    {
        if (empty($orderNo)) {
            return false;
        }

        return true;
    }

    private function checkDesc($proDesc)
    {
        if (empty($proDesc)) {
            throw new \Exception('請設定刷卡描述');
        }
    }
}
