<?php

namespace Twdd\Listeners;

use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Facades\Infobip;
use Twdd\Services\Task\TaskNo;

class SpgatewayFailSmsListener
{
    public $task;
    public $result;

    /**
     * Handle the event.
     *
     * @param ExampleEvent $event
     * @return void
     */
    public function handle(SpgatewayErrorEvent $event)
    {
        $this->task = $event->task;
        $this->result = $event->result;

        $this->sms();
    }

    private function sms(){

        $body = '台灣代駕通知：您的任務（單號: '.TaskNo::make($this->task->id).'）因銀行回報授權失敗無法刷卡！若司機改以現金結帳請放心把現金交給司機。';
        Infobip::sms()->send($this->task->member->UserPhone, $body);
    }
}