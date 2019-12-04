<?php


namespace Twdd\Listeners;


use Illuminate\Support\Facades\Log;
use Twdd\Facades\CouponService;
use Twdd\Models\Task;
use Twdd\Repositories\DriverDayNumsRepository;
use Twdd\Repositories\TaskRepository;

class TwddEventSubscriber
{
    private function  couponSetUsed(Task $task){
        Log::info('TaskDoneEventSubscriber couponSetUsed');
        CouponService::task($task)->setUsed();
    }

    private function updateDriverDayNums(Task $task){
        Log::info('TaskDoneEventSubscriber updateDriverDayNums');
        $driverDayNumsRepository = app(DriverDayNumsRepository::class);
        $taskRepository = app(TaskRepository::class);

        if(isset($task->id) && isset($task->driver_id)){
            $result = $taskRepository->sumAndNumsFromTaskFeeByDriverAndDate($task->driver_id, date('Y-m-d').'%');
            Log::info('TaskDoneEventSubscriber updateDriverDayNums result: ', [$result]);
            $driverDayNumsRepository->insertByDriverId($task->driver_id, $result->nums, $result->money);
        }
    }

    public function taskDone($event){
        $this->couponSetUsed($event->task);
        $this->updateDriverDayNums($event->task);
    }

    public function subscribe($events){

        $events->listen(
            'Twdd\Events\TaskDoneEvent',
                'Twdd\Listeners\TaskDoneEventSubscriber@taskDone'
        );

        $events->listen(
            'Twdd\Events\SpgatewayFailEvent',
            'Twdd\Listeners\TaskDoneEventSubscriber@taskDone'
        );
    }

}