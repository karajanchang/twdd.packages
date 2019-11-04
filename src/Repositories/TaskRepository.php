<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;


use Twdd\Criterias\Task\TaskStateInProcess;


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

    public function checkNotHaveInProcessTaskByDriverId($driver_id){

        $taskStateUnder3 = app()->make(TaskStateInProcess::class);
        $this->pushCriteria($taskStateUnder3);
        $tasks = $this->findWhere([
            'driver_id' => $driver_id,
        ], ['id']);

        if(count($tasks)>0){

            return true;
        }

        return false;
    }

    public function nums7ByUserCreditCodeAndMember(string $UserCreditCode, int $member_id){

        return $this->where('UserCreditCode', $UserCreditCode)->where('TaskState', 7)->where('member_id', $member_id)->count();
    }

    public function isPay(int $id, int $TaskFee, int $twddFee, int $is_first_use = 0, int $member_creditcard_id = 0){
        $params = [
            'is_pay' => 1,
            'is_first_use' => $is_first_use,
            'twddFee' => $twddFee,
            'TaskFee' => $TaskFee,
            'member_creditcard_id' => $member_creditcard_id,
        ];

        return $this->update($id, $params);
    }

}
