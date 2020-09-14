<?php

namespace Twdd\Listeners;

use Twdd\Events\ApplePayErrorEvent;
use Twdd\Facades\Infobip;
use Twdd\Services\Task\TaskNo;

class ApplePayErrorSmsListener
{
    use TraitPayTime;

    public $task;

    /**
     * Handle the event.
     *
     * @param ApplePayErrorEvent $event
     * @return void
     */
    public function handle(ApplePayErrorEvent $event)
    {
        $this->task = $event->task;

        $this->sms();
    }

    private function sms(){

        $time = $this->payTime();
        $body = '台灣代駕通知：您的代駕服務 '.$time.'（單號: '.TaskNo::make($this->task->id).') ApplePay付款異常，無法結帳！';

        Infobip::sms()->send($this->task->member->UserPhone, $body);
    }
}