<?php
//--刷卡失敗

namespace Twdd\Subscribers;


use Twdd\Listeners\SpgatewayFailMailListener;
use Twdd\Listeners\SpgatewayFailSmsListener;

class SpgateFailSubscriber
{
    public function handle($event){
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