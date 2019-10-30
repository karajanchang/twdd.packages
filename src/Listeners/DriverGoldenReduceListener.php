<?php


namespace Twdd\Listeners;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Twdd\Events\TaskDoneEvent;
use Twdd\Repositories\DriverGoldAlterRecordRepository;
use Twdd\Repositories\DriverRepository;

class DriverGoldenReduceListener
{
    private $task;
    private $driverGoldAlterRecordRepository;
    private $driverRepisotyry;

    /**
     * LineUseFirstDiscountListener constructor.
     * @param DriverGoldAlterRecordRepository $driverGoldAlterRecordRepository
     * @param DriverRepository $driverRepository
     */
    public function __construct(DriverGoldAlterRecordRepository $driverGoldAlterRecordRepository, DriverRepository $driverRepository)
    {
        $this->driverGoldAlterRecordRepository = $driverGoldAlterRecordRepository;
        $this->driverRepisotyry = $driverRepository;
    }

    public function handle(TaskDoneEvent $taskDoneEvent)
    {
        $this->setTask($taskDoneEvent->task);

        if($this->isUsedGolden()===true){
            DB::beginTransaction();
            try {
                $driver = $this->driverRepisotyry->reduceGoldenNums(true);
                $this->driverGoldAlterRecordRepository->insertByTask($this->task, $driver);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('DriverGoldenReduceListener:'.$e->getMessage(), ['task' => $taskRow]);
                Bugsnag::notifyException($e);
            }
        }
    }

    private function isUsedGolden(){
        if($this->task->is_used_gold != 1){

            return false;
        }

        if($this->driverGoldAlterRecordRepository->countByTask($this->task) > 0){

            return false;
        }

        return true;
    }

    private function setTask(Model $task){
        $this->task = $task;
    }
}