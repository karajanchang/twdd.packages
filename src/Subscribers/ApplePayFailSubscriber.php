<?php


namespace Twdd\Subscribers;


use Illuminate\Support\Facades\Cache;
use Twdd\Listeners\ApplePayFailMailListener;
use Twdd\Listeners\ApplePayFailSmsListener;

class ApplePayFailSubscriber
{
    public function handle($event){
        $task = $event->task;
        if(empty($task->id)){

            return false;
        }
        Cache::increment('SPGATEWAY_PAYMENT_TIMES'.$task->id);
        app(ApplePayFailMailListener::class)->handle($event);
        app(ApplePayFailSmsListener::class)->handle($event);
    }

    public function subscribe($events){
        $events->listen(
            'Twdd\Events\ApplePayFailEvent',
            'Twdd\Subscribers\ApplePayFailSubscriber@handle'
        );
    }

}