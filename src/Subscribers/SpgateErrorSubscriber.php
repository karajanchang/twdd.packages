<?php
//--刷卡異常

namespace Twdd\Subscribers;


use Illuminate\Support\Facades\Cache;
use Twdd\Listeners\SpgatewayErrorMailListener;
use Twdd\Listeners\SpgatewayErrorSmsListener;

class SpgateErrorSubscriber
{

    public function handle($event){
        $task = $event->task;
        if(empty($task->id)){

            return false;
        }
        Cache::increment('SPGATEWAY_PAYMENT_TIMES'.$task->id);
        app(SpgatewayErrorMailListener::class)->handle($event);
        app(SpgatewayErrorSmsListener::class)->handle($event);
    }

    public function subscribe($events){
        $events->listen(
            'Twdd\Events\SpgatewayErrorEvent',
            'Twdd\Subscribers\SpgateErrorSubscriber@handle'
        );
    }
}