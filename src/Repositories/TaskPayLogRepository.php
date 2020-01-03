<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Illuminate\Database\Eloquent\Model;
use Twdd\Models\TaskPayLog;
use Zhyu\Repositories\Eloquents\Repository;

class TaskPayLogRepository extends Repository
{

    public function model()
    {
        return TaskPayLog::class;
    }

    public function insertByTask(Model $task, array $params = []){
        $params['pay_type'] = $task->pay_type;
        $params['task_id'] = $task->id;
        $params['created_at'] = date('Y-m-d H:i:s');
        $params['updated_at'] = date('Y-m-d H:i:s');

        return $this->create($params);
    }


}
