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

    public function insertByParams(array $params = []){
        $params['created_at'] = date('Y-m-d H:i:s');
        $params['updated_at'] = date('Y-m-d H:i:s');
        $params['member_creditcard_id'] = isset($params['member_creditcard_id']) && $params['member_creditcard_id']!=0 ? $params['member_creditcard_id'] : null;

        return $this->create($params);
    }

    public function findByTaskId($task_id = null){
        if(is_null($task_id)) return null;

        return $this->where('task_id', $task_id)->first();
    }

}
