<?php
//--刷卡異常

namespace Twdd\Subscribers;


use Twdd\Listeners\SpgatewayErrorMailListener;
use Twdd\Listeners\SpgatewayErrorSmsListener;

class SpgateErrorSubscriber
{

    public function handle($event){
        app(SpgatewayErrorMailListener::class)->handle($event);
        app(SpgatewayErrorSmsListener::class)->handle($event);
    }

    public function subscribe($events){
        $events->listen(
            'Twdd\Events\SpgatewayErrorEvent',
            'Twdd\Listeners\SpgateErrorSubscriber@handle'
        );
    }
}