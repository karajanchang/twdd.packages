<?php

namespace Twdd\Listeners;

use Twdd\Events\SpgatewayFailEvent;
use Twdd\Facades\Infobip;
use Twdd\Services\Task\TaskNo;

class SpgatewayFailSmsListener
{
    use TraitPayTime;

    public $task;
    public $result;

    /**
     * Handle the event.
     *
     * @param ExampleEvent $event
     * @return void
     */
    public function handle(SpgatewayFailEvent $event)
    {
        $this->task = $event->task;
        $this->result = $event->result;

        $this->sms();
    }

    private function sms(){

        $time = $this->payTime();
        $body = '台灣代駕通知：您的代駕服務 '.$time.'（單號: '.TaskNo::make($this->task->id).') 信用卡銀行回應刷卡失敗！';
        Infobip::sms()->send($this->task->member->UserPhone, $body);
    }


}