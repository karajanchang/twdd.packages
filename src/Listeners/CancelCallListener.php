<?php
namespace Twdd\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Twdd\Events\CancelCall;

class CancelCallListener
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
    public function handle(CancelCall $event)
    {
        $call = $event->call;

        $call->is_cancel = 1;

        $call->save();
        Log::info('call cancel', [$call]);
    }
}

