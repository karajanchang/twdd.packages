<?php

namespace Twdd\Listeners;

use Illuminate\Support\Facades\Mail;
use Twdd\Events\ApplePayErrorEvent;
use Twdd\Mail\System\InfoAdminMail;

class ApplePayErrorMailListener
{
    public $task;
    public $result = null;

    /**
     * Handle the event.
     *
     * @param ApplePayErrorEvent $event
     * @return void
     */
    public function handle(ApplePayErrorEvent $event)
    {
        $this->task = $event->task;
        $this->result = $event->result;

        $this->mail();
    }

    private function mail(){
        $infoAdminMail = new InfoAdminMail('［系統通知］!!!ApplePay，結帳異常!!!', '結帳異常 (單號：' . $this->task->id . ')');

        $emails = explode(',', env('ADMIN_NOTIFY_EMAilS', 'service@twdd.com.tw'));
        if(count($emails)) {
            Mail::to($emails)->queue($infoAdminMail);
        }
    }
}