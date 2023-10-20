<?php

namespace Twdd\Services\Invoice\Type;

use Twdd\Services\Invoice\AbstractSetting;
use Twdd\Services\Invoice\InvoiceInterface;
use Twdd\Repositories\EcpayInvoiceRepository;
use Twdd\Models\Enterprise;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Models\Task;

class B2B extends AbstractSetting implements InvoiceInterface
{
    public $payment;
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    public function issue()
    {
        $url = $this->url . "/issue/B2BInvoice";
        $params = $this->getParams();

        //B2B交易前要先呼叫"交易對象維護"API
        $result = $this->maintain();

        if (!$result['code']) {
            $id = $this->payment["id"] ?? 'unknown';
            return [
                'status' => 2,
                'err' => $result["error"],
                'msg' => '發票開立失敗, 來源為B2B帳單:' . $id
            ];
        }

        //B2B因為有混合未稅(一般代駕)及含稅(鐘點代駕)的部分
        //enterprise_bill裡面存的是含稅價
        //但api需要從未稅價開始給, 因此這邊的price跟amount(prise * count)需要除以1.05
        $callData = [
            "taxId" => $this->enterprise->GUI_number,
            "item" => [
                "itemSeq" => 1,
                "itemName" => "軟體交易平台服務費",
                "itemCount" => 1,
                "itemWord" => "次",
                "itemPrice" => round($params['fee'] / 1.05, 0),
                "itemAmount" => round($params['fee'] / 1.05, 0)
            ]
        ];


        $result = $this->call($url, $callData);

        if (!$result['code']) {

            $id = $this->payment["id"] ?? 'unknown';
            return [
                'status' => 2,
                'err' => $result["error"],
                'msg' => '發票開立失敗, 來源為B2B帳單:' . $id
            ];
        }

        $invoice = $this->store($result);
        return ['invoice' => $invoice, 'status' => 1];
    }

    private function maintain()
    {
        $url = $this->url . "/set";
        //因為帳務聯絡人不一定有, 所以需要預設值
        if (isset($this->enterprise->accountContact->email)) {
            $email = sprintf("%s;%s", $this->enterprise->mainContact->email, $this->enterprise->accountContact->email);
        } else {
            $email = $this->enterprise->mainContact->email ?? $this->enterprise->UserEmail;
        }

        $setting = [
            "Identifier" => $this->enterprise->GUI_number,
            "CustomerNumber" => $this->enterprise->GUI_number,
            "CompanyName" => $this->enterprise->title,
            "EmailAddress" => $email
        ];

        return $this->call($url, $setting);
    }


    public function invalid()
    {
        $url = $this->url . "/invalid/B2BInvoice";
        $params = $this->getParams();

        $callData = [
            "InvoiceNumber" => $params['model']->ecpay_invoice->invoice_number,
            "InvoiceDate" => $params['model']->ecpay_invoice->created_at->format('Y-m-d'),
            "Reason" => "鐘點代駕取消服務"
        ];

        $result = $this->call($url, $callData, 'PUT');

        if (!$result['code']) {

            $id = $this->payment["id"] ?? 'unknown';
            return [
                'status' => 4,
                'err' => $result["error"],
                'msg' => '發票作廢失敗, 發票號碼為:' . $params['model']->ecpay_invoice->invoice_number
            ];
        }

        $this->delete($params['model']->ecpay_invoice->id);
        return ['status' => 3];
    }

    public function store($data)
    {
        $params = $this->getParams();
        $invoiceData = [
            'relate_number' => $data['callback']['RelateNumber'],
            'invoice_number' =>  $data['callback']['InvoiceNo'],
            'invoice_type' => 2,
            'invoice_amount' => $params['fee'],
        ];

        //乘客打統編也會變成btob, 所以要檢查來源
        if ($this->payment instanceof CalldriverTaskMap) {
            $invoiceData['calldriver_task_map_id'] = $this->payment["id"] ?? null;
            if ($this->task) {
                $invoiceData['task_id'] = $this->task->id;
            }
        } else if ($this->payment instanceof Task){
            $invoiceData['task_id'] =  $this->payment["id"] ?? null;
            $calldriver = $this->payment->load('calldriver_task_map');
            $invoiceData['calldriver_task_map_id'] = $this->payment->calldriver_task_map->id ?? null;
        } else {
            $invoiceData['enterprise_payment_id'] = $this->payment["id"] ?? null;
        }

        return app(EcpayInvoiceRepository::class)->create($invoiceData);
    }

    public function delete($id)
    {
        return app(EcpayInvoiceRepository::class)->softDelete($id);
    }
}
