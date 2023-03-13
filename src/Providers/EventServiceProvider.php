<?php


namespace Twdd\Providers;

use Illuminate\Events\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    
    // protected $listen = [
    //     /*
    //     //--刷卡失敗
    //     'Twdd\Events\SpgatewayFailEvent' => [
    //         'Twdd\Listeners\SpgatewayFailMailListener',
    //         'Twdd\Listeners\SpgatewayFailSmsListener',
    //     ],
    //     //--刷卡異常
    //     'Twdd\Events\SpgatewayErrorEvent' => [
    //         'Twdd\Listeners\SpgatewayErrorMailListener',
    //         'Twdd\Listeners\SpgatewayErrorSmsListener',
    //     ],
    //     */
    //     BlackhatReserveMailEvent::class => [
    //         BlackhatReserveMailListener::class,
    //     ],
    // ];

    public function boot()
    {
        $this->app['events']->listen(
            \Twdd\Events\BlackhatReserveMailEvent::class,
            \Twdd\Events\BlackhatReserveMailListener::class
        );

        $this->app['events']->listen(
            \Twdd\Events\InvoiceIssueEvent::class,
            \Twdd\Listeners\InvoiceIssueListener::class
        );

        $this->app['events']->listen(
            \Twdd\Events\InvoiceMailEvent::class,
            \Twdd\Listeners\InvoiceMailListener::class
        );

        $this->app['events']->listen(
            \Twdd\Events\InvoiceInvalidEvent::class,
            \Twdd\Listeners\InvoiceInvalidListener::class
        );
    }
    
}