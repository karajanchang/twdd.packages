<?php


namespace Twdd\Listeners;

use Twdd\Events\TaskDoneEvent;
use Twdd\Facades\CouponService;

class CouponSetUsedListener
{
    private $repository;

    /**
     * CouponSetUsedListener constructor.
     */
    public function __construct()
    {
    }

    public function handle(TaskDoneEvent $taskDoneEvent){
        CouponService::task($taskDoneEvent->task)->setUsed();
    }
}