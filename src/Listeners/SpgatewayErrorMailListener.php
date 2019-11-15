<?php

namespace Twdd\Listeners;

use Illuminate\Support\Facades\Mail;
use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Mail\System\InfoAdminMail;

class SpgatewayErrorMailListener
{
    public $task;
    public $result = null;

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

        $this->mail();
    }

    private function mail(){
        $infoAdminMail = new InfoAdminMail('［系統通知］!!!智付通，刷卡異常!!!', '刷卡異常 (單號：' . $this->task->id . ')');

        $emails = explode(',', env('ADMIN_NOTIFY_EMAilS', 'service@twdd.com.tw'));
        if(count($emails)) {
            Mail::to($emails)->queue($infoAdminMail);
        }
    }
}