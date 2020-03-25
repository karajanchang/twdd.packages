<?php


namespace Twdd\Subscribers;


use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Twdd\Facades\CouponService;
use Twdd\Facades\TwddCache;
use Twdd\Listeners\MultiCallCouponListener;
use Twdd\Models\Task;
use Twdd\Repositories\DriverDayNumsRepository;
use Twdd\Repositories\MonthMoneyDriverRepository;
use Twdd\Repositories\TaskRepository;

class TaskDoneEventSubscriber
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
            $result = $taskRepository->sumAndNumsFromTaskFeeByDriverAndDate($task->driver_id, date('Y'), date('n'), date('j'));
            Log::info('TaskDoneEventSubscriber updateDriverDayNums result: ', [$result]);
            $driverDayNumsRepository->insertByDriverId($task->driver_id, $result->nums, $result->money);
        }
    }

    private function updateMonthMoneyDriver(Task $task){
        if(empty($task->createtime)){

            return false;
        }

        $taskRepository = app(TaskRepository::class);
        $result = $taskRepository->sumAndNumsFromTaskFeeByDriverAndDate($task->driver_id, date('Y'), date('n'));
        $params = [
            'nums' => $result->nums,
            'sumTaskFee' => $result->money,
            'money' => $result->money - $result->sumTwddFee,
        ];

        $dt = Carbon::parse($task->createtime);
        Log::info('TwddEventSubscriber updateMonthMoneyDriver 更新司機每月業績 taskno ('.$task->id.'):', ['dt' => $dt, 'params' => $params]);
        $res = app(MonthMoneyDriverRepository::class)->createOrUpdateByDriverId($task->driver_id, $dt, $params);

        return $res;
    }

    private function clearCache(Task $task){
        if(isset($task->driver)){

            return false;
        }

        ClearTaskCache($task);

        return true;
    }

    private function settingLongTermExtraPriceMap($task)
    {
        //4為長途代駕
        if ($task->call_type != 4) {
            return ;
        }
        if ((int) $task->extra_price == 0) {
            return ;
        }

        $taskExtraPrices = TaskExtraPrice::where('task_id', $task->id)->get();
        if ($taskExtraPrices->count() == 0) {
            return ;
        }

        $taskExtraPrices->each(function ($extraPrice, $key) {
            TaskExtraPrice::where('id', $extraPrice->id)->update(['extra_price' => $extraPrice->extra_price*2]);
            Log::info('before extra_price:'.$extraPrice->id.$extraPrice->extra_price);
        });
    }

    public function taskDone($event){
        $this->couponSetUsed($event->task);
        $this->updateDriverDayNums($event->task);
        $this->updateMonthMoneyDriver($event->task);
        $this->clearCache($event->task);

        //---多人送獎勵
        $multiCallCouponListener = app(MultiCallCouponListener::class);
        $multiCallCouponListener->handle($event->task);

    }

    public function subscribe($events){
        $events->listen(
            'Twdd\Events\TaskDoneEvent',
            'Twdd\Subscribers\TaskDoneEventSubscriber@taskDone'
        );
    }

}