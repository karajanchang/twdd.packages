<?php

namespace Twdd\Listeners;

use Twdd\Events\ApplePayFailEvent;
use Twdd\Facades\Infobip;
use Twdd\Services\Task\TaskNo;

class ApplePayFailSmsListener
{
    use TraitPayTime;

    public $task;
    public $result;

    /**
     * Handle the event.
     *
     * @param ApplePayFailEvent $event
     * @return void
     */
    public function handle(ApplePayFailEvent $event)
    {
        $this->task = $event->task;
        $this->result = $event->result;

        $this->sms();
    }

    private function sms(){

        $time = $this->payTime();
        $body = '台灣代駕通知：您的代駕服務 '.$time.'（單號: '.TaskNo::make($this->task->id).') ApplePay回應付款失敗！';
        Infobip::sms()->send($this->task->member->UserPhone, $body);
    }


}