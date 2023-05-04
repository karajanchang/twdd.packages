<?php

namespace Twdd\Services\Invoice\Type;

use Twdd\Services\Invoice\AbstractSetting;
use Twdd\Services\Invoice\InvoiceInterface;
use Twdd\Repositories\EcpayInvoiceRepository;

class B2C extends AbstractSetting implements InvoiceInterface
{

    public function issue()
    {
        $url = $this->url . "/issue/B2CInvoice";
        $params = $this->getParams();

        $callData = [
            "phone" => $this->member->UserPhone,
            "email" => $this->member->UserEmail,
            "item" => [
                "itemSeq" => 1,
                "itemName"=> "軟體交易平台服務費",
                "itemCount"=> 1,
                "itemWord"=> "次",
                "itemPrice"=> $params['fee'],
                "itemAmount"=> $params['fee']
            ]
        ];


        $result = $this->call($url,$callData);

        if (!$result['code']) {

            if (isset($this->task->id)){
                $msg = '發票開立失敗, 來源任務編號:'.$this->task->id;
            }else{
                $msg = '發票開立失敗, 來源呼叫編號:'.$this->calldriverTaskMap->id;
            }

            return [
                'status' => 2,
                'err'=>$result["error"],
                'msg' => $msg
            ];
        }

        $invoice = $this->store($result);
        return ['invoice'=>$invoice,'status'=> 1];
    }


    public function invalid()
    {
        $url = $this->url . "/invalid/B2CInvoice";
        $params = $this->getParams();

        $callData = [
            "InvoiceNo" => $params['model']->ecpay_invoice->invoice_number,
            "InvoiceDate"=> $params['model']->ecpay_invoice->created_at->format('Y-m-d'),
            "Reason"=> "鐘點代駕取消服務"
        ];

        $result = $this->call($url,$callData,'PUT');
        
        if (!$result['code']) {
            return [
                'status' => 4,
                'err'=>$result["error"],
                'msg' => '發票作廢失敗, 發票號碼為:'.$params['model']->ecpay_invoice->invoice_number
            ];
        }

        $this->delete($params['model']->ecpay_invoice->id);
        return ['status'=>3];
    }

    public function store($data)
    {
        $params = $this->getParams();
        $invoiceData = [
            'relate_number' => $data['callback']['RelateNumber'],
            'invoice_number' =>  $data['callback']['InvoiceNo'],
            'invoice_type' => 1,
            'invoice_amount' => $params['fee'],
            'calldriver_task_map_id' => $this->calldriverTaskMap->id
        ];

        if ($this->task){
            $invoiceData['task_id'] = $this->task->id;
        }

        return app(EcpayInvoiceRepository::class)->create($invoiceData);
    }

    public function delete($id)
    {
        return app(EcpayInvoiceRepository::class)->softDelete($id);
    }
}
