<?php

namespace Twdd\Jobs\Invoice;

use Twdd\Jobs\Job;
use Twdd\Facades\InvoiceFactory;
use Twdd\Facades\InvoiceService;
use Illuminate\Support\Facades\Log;
use Twdd\Models\Task;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Jobs\Invoice\InvoiceMailJob;


class InvoiceIssueJob extends Job
{

    private $params;
    private $service;

    /**
     * Params should include:
     *
     * type B2B or B2C
     * fee 費用
     * target model of the enterprise or member
     * belong one of those model: task, calldriver_task_map, enterprise_bill
     * 
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {

        $this->bindType();

        try {
            $result = $this->service->issue();
            if (!isset($result['err'])) {
                $mail = app()->make(InvoiceMailJob::class,[
                    "status" => $result['status'],
                    "msg" => sprintf('發票開立成功, 發票編號: %s; 自訂編號為: %s', $result['invoice']['invoice_number'], $result['invoice']['relate_number'])
                ]);
            } else {
                $mail = app()->make(InvoiceMailJob::class,$result);
            }
            dispatch($mail);
        } catch (\Throwable $e) {
            if ($this->params['belong'] instanceof CalldriverTaskMap) {
                $title = "calldriver_task_map_id";
            } else if ($this->params['belong'] instanceof Task) {
                $title = "task_id";
            } else {
                $title = "enterprise_bill_id";
            }
            $id = $this->params['belong']['id'] ?? 'unknown';
            $msg = sprintf('發票API error, $s: $s ', $title, $id);
            Log::error(__CLASS__ . '::' . __METHOD__ . ' error: ' . $msg, [$e]);
        }
    }

    private function bindType()
    {
        $factory = app()->make(\Twdd\Helpers\InvoiceFactory::class);
        $factory->bind($this->params["type"]);
        
        if ($this->params["type"] == "B2B") {
            $this->service = InvoiceService::enterprise($this->params["target"]);
        } else {

            $this->service = InvoiceService::member($this->params["target"]);

            if ($this->params['belong'] instanceof CalldriverTaskMap) {
                $this->service->calldriverTaskMap($this->params['belong']);
            } else {
                $this->service->task($this->params['belong']);
            }
        }

        $this->service->setParams($this->params);
    }
}
