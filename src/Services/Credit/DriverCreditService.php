<?php
namespace Twdd\Services\Credit;

use Illuminate\Support\Carbon;
use Twdd\Models\Driver;
use Twdd\Models\DriverCreditChange;

class DriverCreditService
{
    public function __construct(){}

    public function charge($task, $type, $credit)
    {
        $driver = Driver::query()->where('id', $task->driver_id)->first();
        $driverCredit = $driver->DriverCredit;
        $driverCreditAfter = $driverCredit + $credit;

        $params = [
            'driver_id' => $task->driver_id,
            'task_id' => $task->id,
            'type' => $type,
            'credit' => $credit,
            'driver_credit_before' => $driverCredit,
            'driver_credit_after'  => $driverCreditAfter,
            'createtime' => Carbon::now(),
        ];

        DriverCreditChange::query()->create($params);
        Driver::query()->where('id', $task->driver_id)
            ->update(['DriverCredit' => $driverCreditAfter]);

    }
}
