<?php


namespace Twdd\Listeners;


use Twdd\Events\TaskDoneEvent;
use Twdd\Repositories\DriverDayNumsRepository;
use Twdd\Repositories\TaskRepository;

class DriverDayNumsListener
{
    /**
     * @var DriverDayNumsRepository
     */
    private $driverDayNumsRepository;
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    public function __construct(DriverDayNumsRepository $driverDayNumsRepository, TaskRepository $taskRepository)
    {
        $this->driverDayNumsRepository = $driverDayNumsRepository;
        $this->taskRepository = $taskRepository;
    }

    public function handle(TaskDoneEvent $taskDoneEvent){
        $task = $taskDoneEvent->task;
        if(isset($task->id) && isset($task->driver_id)){
            $result = $this->taskRepository->sumAndNumsFromTaskFeeByDriverAndDate($task->driver_id, date('Y-m-d').'%');
            $this->driverDayNumsRepository->insertByDriverId($task->driver_id, $result->nums, $result->money);
        }
    }
}