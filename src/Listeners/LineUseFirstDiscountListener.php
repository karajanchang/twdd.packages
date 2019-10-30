<?php


namespace Twdd\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Twdd\Events\TaskDoneEvent;
use Twdd\Jobs\Task\LineUseFirstDiscount;

class LineUseFirstDiscountListener
{
    /**
     * LineUseFirstDiscountListener constructor.
     */
    public function __construct()
    {
    }

    public function handle(TaskDoneEvent $taskDoneEvent){
        //---使用line把use_first_discount=1
        dispatch(new LineUseFirstDiscount($this->taskDoneEvent->task));
    }
}