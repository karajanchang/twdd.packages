<?php

namespace Twdd\Listeners;

use Throwable;
use Twdd\Events\InvoiceInvalidEvent;
use Twdd\Events\InvoiceMailEvent;
use Twdd\Facades\InvoiceFactory;
use Twdd\Facades\InvoiceService;
use Illuminate\Support\Facades\Log;
use Twdd\Models\Task;
use Twdd\Models\CalldriverTaskMap;


class InvoiceInvalidListener
{

    private $params;
    private $service;
    /**
     * Handle the event.
     *
     * @param InvoiceInvalidEvent $event
     * @return void
     */
    public function handle(InvoiceInvalidEvent $event)
    {
        $this->params = $event->params;
        $this->bindType();
        
        $this->params["model"]->load('ecpay_invoice');

        $this->service->setParams($this->params);
        try{
            $result = $this->service->invalid();

            if (!isset($result['err'])){

                event(new InvoiceMailEvent([
                    "status" => $result['status'],
                    "msg" => sprintf('發票作廢成功, 發票編號: %s; 自訂編號為: %s',$this->params["model"]->ecpay_invoice->invoice_number,$this->params["model"]->ecpay_invoice->relate_number,)
                ]));
            }else{

                event(new InvoiceMailEvent($result));
            }
        }catch(Throwable $e){
            
            $msg = sprintf('作廢發票API error, id: $s ',$this->params["model"]->ecpay_invoice->id); 
            Log::error(__CLASS__ . '::' . __METHOD__ . ' error: ' . $msg, [$e]);
        }
    }

    private function bindType()
    {
        InvoiceFactory::bind($this->params["type"]);

        $this->service = InvoiceService::calldriverTaskMap($this->params["model"]);
    }
}