<?php


namespace Twdd\Repositories;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Twdd\Models\DriverGoldAlterRecord;
use Zhyu\Repositories\Eloquents\Repository;

class DriverGoldAlterRecordRepository extends Repository
{
    public function model(){

        return DriverGoldAlterRecord::class;
    }

    public function countByTask(Model $task){

        return $this->where('driver_id', $task->driver_id)->where('task_id', $task->id)->count();
    }

    /*
     * 新增叩除記錄
     * operation_type 2 表示 叩除
     */
    public function insertMinusRecordByTask(Model $task, bool $is_minus_by_member_rated = false){
        //--1: 任務使用, 2: 每月發放, 3: 異動紀錄, 4: 用戶評分
        $assemble_type = $is_minus_by_member_rated===true ? 4 : 1;
        $comments = $is_minus_by_member_rated===true ? TaskNo($task->id).'服務獲得１星評價扣除１張金牌' : '';

        return $this->create([
            'assemble_type' => $assemble_type,
            'operation_type' => 2,
            'driver_id' => $task->driver_id,
            'comments' => $comments,
            'gold_num' => $task->driver->driver_gold_nums,
            'change_num' => 1,
            'task_id' => $task->id,
        ]);
    }
}