<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Twdd\Criterias\Task\TaskStateInProcess;
use Twdd\Models\Member;
use Twdd\Models\Task;
use Zhyu\Repositories\Eloquents\Repository;

class TaskRepository extends Repository
{

    public function model()
    {
        return Task::class;
    }

    public function checkNotHaveInProcessTaskByMemberId($member_id){
        $taskStateUnder3 = app()->make(TaskStateInProcess::class);
        $this->pushCriteria($taskStateUnder3);
        $tasks = $this->findWhere([
            'member_id' => $member_id,
        ], ['id']);

        if(count($tasks)>0){

            return true;
        }

        return false;
    }

}
