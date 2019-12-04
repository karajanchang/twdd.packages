<?php


namespace Twdd\Providers;

use Illuminate\Events\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        //--刷卡失敗
        'Twdd\Events\SpgatewayFailEvent' => [
            'Twdd\Listeners\SpgatewayErrorMailListener',
            'Twdd\Listeners\SpgatewayFailSmsListener',
        ],
        //--刷卡異常
        'Twdd\Events\SpgatewayErrorEvent' => [
            'Twdd\Listeners\SpgatewayFailMailListener',
            'Twdd\Listeners\SpgatewayErrorSmsListener',
        ],
    ];
}