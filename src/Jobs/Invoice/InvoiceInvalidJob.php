<?php

namespace Twdd\Jobs\Invoice;

use App\Jobs\Job;
use Twdd\Facades\InvoiceFactory;
use Twdd\Facades\InvoiceService;
use Illuminate\Support\Facades\Log;
use Twdd\Models\Task;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Jobs\Invoice\InvoiceMailJob;


class InvoiceInvalidJob extends Job
{

    private $params;
    private $service;

    /**
     * Params should include:
     *
     * type B2B or B2C
     * model call_driver_task_map's model
     * 
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {

        $this->bindType();

        $this->params["model"]->load('ecpay_invoice');

        $this->service->setParams($this->params);
        try {
            $result = $this->service->invalid();

            if (!isset($result['err'])) {
                $mail = app()->make(InvoiceMailJob::class,[
                    "status" => $result['status'],
                    "msg" => sprintf('發票作廢成功, 發票編號: %s; 自訂編號為: %s', $this->params["model"]->ecpay_invoice->invoice_number, $this->params["model"]->ecpay_invoice->relate_number)
                ]);

            } else {
                $mail = app()->make(InvoiceMailJob::class,$result);
            }

            dispatch($mail);
        } catch (\Throwable $e) {

            $msg = sprintf('作廢發票API error, id: $s ', $this->params["model"]->ecpay_invoice->id);
            Log::error(__CLASS__ . '::' . __METHOD__ . ' error: ' . $msg, [$e]);
        }
    }

    private function bindType()
    {
        $factory = app()->make(\Twdd\Helpers\InvoiceFactory::class);
        $factory->bind($this->params["type"]);

        $this->service = InvoiceService::calldriverTaskMap($this->params["model"]);
    }
}
