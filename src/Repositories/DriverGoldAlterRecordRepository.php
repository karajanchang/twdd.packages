<?php


namespace Twdd\Repositories;


use Illuminate\Database\Eloquent\Model;
use Twdd\Models\Driver;
use Twdd\Models\DriverGoldAlterRecord;
use Twdd\Models\Task;
use Zhyu\Repositories\Eloquents\Repository;

class DriverGoldAlterRecordRepository extends Repository
{
    public function model(){

        return DriverGoldAlterRecord::class;
    }

    public function countByTask(Model $task){

        return $this->where('driver', $task->driver_id)->where('task_id', $task->id)->count();
    }

    public function insertByTaskAndDriver(Task $task, Driver $driver){

        $this->insert([
            'assemble_type' => 1,
            'operation_type' => 2,
            'driver_id' => $driver->id,
            'comments' => '',
            'gold_num' => $driver->driver_gold_nums,
            'change_num' => 1,
            'task_id' => $task->id,
        ]);
    }
}