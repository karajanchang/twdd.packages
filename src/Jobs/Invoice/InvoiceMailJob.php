<?php
namespace Twdd\Jobs\Invoice;

use Twdd\Jobs\Job;

use Twdd\Mail\Invoice\InvoiceMail;
use Illuminate\Support\Facades\Mail;

class InvoiceMailJob extends Job
{

    private $params;
    private $targetEmail;

    /**
     * Params should include:
     *
     * status 1:開立成功;2:開立失敗;3:作廢成功;4:作廢失敗;
     * msg 包含呼叫編號(calldriver_task_map)或任務編號或帳單編號的提示訊息
     * err 未成功開立的原因
     * 
     */
    public function __construct($params)
    {
        $this->params = $params;
        $this->targetEmail = env('APP_TYPE', 'development')=='production' ? env('INVOICE_API_MAIL', 'finance@twdd.com.tw') : env('INVOICE_API_MAIL', 'ian@twdd.com.tw');
    }

    public function handle(){
        $mail = app()->make(InvoiceMail::class,$this->params);

        Mail::to($this->targetEmail)->send($mail);
    }
}