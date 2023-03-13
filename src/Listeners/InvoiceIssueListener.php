<?php

namespace Twdd\Listeners;

use Throwable;
use Twdd\Events\InvoiceIssueEvent;
use Twdd\Events\InvoiceMailEvent;
use Twdd\Facades\InvoiceFactory;
use Twdd\Facades\InvoiceService;
use Illuminate\Support\Facades\Log;
use Twdd\Models\Task;
use Twdd\Models\CalldriverTaskMap;


class InvoiceIssueListener
{

    private $params;
    private $service;
    /**
     * Handle the event.
     *
     * @param InvoiceIssueEvent $event
     * @return void
     */
    public function handle(InvoiceIssueEvent $event)
    {

        $this->params = $event->params;
        $this->bindType();
        
        try{
            $result = $this->service->issue();

            if (!isset($result['err'])){

                event(new InvoiceMailEvent([
                    "status" => $result['status'],
                    "msg" => sprintf('發票開立成功, 發票編號: %s; 自訂編號為: %s',$result['invoice']['invoice_number'],$result['invoice']['relate_number'])
                ]));
            }else{
                event(new InvoiceMailEvent($result));
            }
        }catch(Throwable $e){
            if ($this->params['belong'] instanceof CalldriverTaskMap){
                $title = "calldriver_task_map_id";
            }else if ($this->params['belong'] instanceof Task){
                $title = "task_id";
            }else{
                $title = "enterprise_bill_id";
            }
            $id = $this->params['belong']['id'] ?? 'unknown';
            $msg = sprintf('發票API error, $s: $s ',$title,$id); 
            Log::error(__CLASS__ . '::' . __METHOD__ . ' error: ' . $msg, [$e]);
        }
    }

    private function bindType()
    {
        InvoiceFactory::bind($this->params["type"]);
        if ($this->params["type"] == "B2B"){
            $this->service = InvoiceService::enterprise($this->params["target"]);
        }else{

            $this->service = InvoiceService::member($this->params["target"]);

            if ($this->params['belong'] instanceof CalldriverTaskMap){
                $this->service->calldriverTaskMap($this->params['belong']);
            }else{
                $this->service->task($this->params['belong']);
            }
        }

        $this->service->setParams($this->params);
    }
}