<?php


namespace Twdd\Subscribers;


use Illuminate\Support\Facades\Cache;
use Twdd\Listeners\ApplePayErrorMailListener;
use Twdd\Listeners\ApplePayErrorSmsListener;

class ApplePayErrorSubscriber
{
    public function handle($event){
        $task = $event->task;
        if(empty($task->id)){

            return false;
        }
        Cache::increment('SPGATEWAY_PAYMENT_TIMES'.$task->id);
        app(ApplePayErrorMailListener::class)->handle($event);
        app(ApplePayErrorSmsListener::class)->handle($event);
    }

    public function subscribe($events){
        $events->listen(
            'Twdd\Events\ApplePayErrorEvent',
            'Twdd\Subscribers\ApplePayErrorSubscriber@handle'
        );
    }

}