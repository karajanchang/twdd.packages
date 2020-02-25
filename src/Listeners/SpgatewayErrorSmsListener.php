<?php

namespace Twdd\Listeners;

use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Facades\Infobip;
use Twdd\Services\Task\TaskNo;

class SpgatewayErrorSmsListener
{
    use TraitPayTime;

    public $task;

    /**
     * Handle the event.
     *
     * @param ExampleEvent $event
     * @return void
     */
    public function handle(SpgatewayErrorEvent $event)
    {
        $this->task = $event->task;

        $this->sms();
    }

    private function sms(){

        $time = $this->payTime();
        $body = '台灣代駕通知：您的代駕服務 '.$time.'（單號: '.TaskNo::make($this->task->id).') 信用卡銀行連線異常，無法刷卡！';
        Infobip::sms()->send($this->task->member->UserPhone, $body);
    }
}