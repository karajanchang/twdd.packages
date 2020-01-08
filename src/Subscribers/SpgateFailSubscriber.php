<?php
//--刷卡失敗

namespace Twdd\Subscribers;


use Illuminate\Support\Facades\Cache;
use Twdd\Listeners\SpgatewayFailMailListener;
use Twdd\Listeners\SpgatewayFailSmsListener;

class SpgateFailSubscriber
{
    public function handle($event){
        $task = $event->task;
        if(empty($task->id)){

            return false;
        }
        Cache::increment('SPGATEWAY_PAYMENT_TIMES'.$task->id);
        app(SpgatewayFailMailListener::class)->handle($event);
        app(SpgatewayFailSmsListener::class)->handle($event);
    }

    public function subscribe($events){
        $events->listen(
            'Twdd\Events\SpgatewayFailEvent',
            'Twdd\Subscribers\SpgateFailSubscriber@handle'
        );
    }
}