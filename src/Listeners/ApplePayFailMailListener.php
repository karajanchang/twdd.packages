<?php

namespace Twdd\Listeners;

use Illuminate\Support\Facades\Mail;
use Twdd\Events\ApplePayFailEvent;
use Twdd\Mail\System\InfoAdminMail;

class ApplePayFailMailListener
{
    public $task;
    public $result = null;

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

        $this->mail();
    }

    private function mail(){
        $infoAdminMail = new InfoAdminMail('［系統通知］ApplePay付款失敗', '刷卡失敗 (單號：' . $this->task->id . ')', $this->result);

        $emails = explode(',', env('ADMIN_NOTIFY_EMAilS', 'service@twdd.com.tw'));
        if(count($emails)) {
            Mail::to($emails)->queue($infoAdminMail);
        }
    }
}