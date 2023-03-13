<?php

namespace Twdd\Listeners;

use Twdd\Events\InvoiceMailEvent;
use Illuminate\Support\Facades\Mail;
use Twdd\Mail\Invoice\InvoiceMail;
use Twdd\Models\DriverTaskExperience;
use Twdd\Models\Driver;

class InvoiceMailListener
{

    public $targetEmail ;

    public function __construct()
    {
        $this->targetEmail = env('APP_TYPE', 'development')=='production' ? 'finance.twdd@gmaiil.com' : 'ian@twdd.com.tw';
    }

    /**
     * Handle the event.
     *
     * @param InvoiceMailEvent $event
     * @return void
     */
    public function handle(InvoiceMailEvent $event)
    {

        $params = $event->params;

        Mail::to($this->targetEmail)->send(new InvoiceMail($params));
    }
}