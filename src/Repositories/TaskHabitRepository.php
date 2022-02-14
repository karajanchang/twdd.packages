<?php

namespace Twdd\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Twdd\Models\TaskHabit;
use Zhyu\Repositories\Eloquents\Repository;

class TaskHabitRepository extends Repository
{

    public function model()
    {
        return TaskHabit::class;
    }

    public function findByTaskId($task_id = null){
        if(is_null($task_id)) return null;

        return $this->where('task_id', $task_id)->orderby('id', 'desc')->first();
    }

}
