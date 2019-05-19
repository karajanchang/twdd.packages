<?php
namespace Twdd\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Twdd\Events\CancelTask;

class CancelTaskListener
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
    public function handle(CancelTask $event)
    {
        $task = $event->task;

        $task->TaskState = -1;
        $task->TaskCancelTS = time();
        $task->isCancelByUser = 1;
        $task->user_cancel_reason_id = (int) $event->user_cancel_reason_id;

        $task->save();
        Log::info('task cancel'.$task->id);
    }
}

