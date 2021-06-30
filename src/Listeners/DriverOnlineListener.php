<?php
namespace Twdd\Listeners;

use Illuminate\Support\Facades\Log;
use Twdd\Events\DriverOnline;

class DriverOnlineListener
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
    public function handle(DriverOnline $event)
    {
        $driver = $event->driver;

        $driver->DriverState = 1;

        $driver->save();
        Log::info('駕駛上線: '.$driver->id);
    }
}

