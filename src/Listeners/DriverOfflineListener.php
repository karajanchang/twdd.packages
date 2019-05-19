<?php
namespace Twdd\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Twdd\Events\DriverOffline;

class DriverOfflineListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ExampleEvent $event
     * @return void
     */
    public function handle(DriverOffline $event)
    {
        $driver = $event->driver;

        $driver->DriverState = 0;

        $driver->save();
        Log::info('司機下線: '.$driver->id);
    }
}

