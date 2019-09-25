<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Illuminate\Support\Facades\DB;
use Twdd\Criterias\Task\TaskStateInProcess;
use Twdd\Models\Calldriver;
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

    public function lastTask(int $driver_id){

        $qb = $this->join('calldriver_task_map', 'calldriver_task_map.task_id', '=', 'task.id')
            ->join('member', 'task.member_id', '=', 'member.id')
            ->leftJoin('member_grade', 'member.member_grade_id', '=', 'member_grade.id')
            ->join('calldriver', 'calldriver_task_map.calldriver_id', '=', 'calldriver.id')
            ->where('calldriver_task_map.driver_id', $driver_id);

        $row = $qb->select(
            DB::raw('LPAD(LTRIM(CAST(task.id AS CHAR)), 8, \'0\') as TaskNo'), 'task.id', 'calldriver_task_map.task_id', DB::raw('calldriver_task_map.id as map_id'), 'calldriver_task_map.is_done', 'calldriver_task_map.is_cancel', 'calldriver_task_map.driver_id', 'calldriver_task_map.member_id', 'calldriver.TS', 'calldriver.type',
            'calldriver.IsMatch', 'calldriver.createtime', DB::raw('calldriver.city as UserCity'), DB::raw('calldriver.district as UserDistrict'), DB::raw('calldriver.addr as UserAddress'), 'calldriver.UserCreditCode', 'calldriver.UserCreditValue', 'calldriver.UserRemark',
            DB::raw('calldriver.lat as UserLat'), DB::raw('calldriver.lon as UserLon'), DB::raw('calldriver.lat_det as DestLat'), DB::raw('calldriver.lon_det as DestLon'), DB::raw('calldriver.city_det as DestCity'), DB::raw('calldriver.district_det as DestDistrict'), DB::raw('calldriver.addr_det as DestAddress'), 'calldriver.extra_price', 'calldriver_task_map.call_type', 'calldriver.pay_type',
            DB::raw('left(member.UserName, 1) as UserName'), 'member.UserGender', 'member.UserPhone', 'task.TaskState', 'task.TaskArriveTS', 'task.TaskStartTS', 'member.member_grade_id', 'cash_fee_discount', 'creditcard_fee_discount', 'can_not_use_coupon', 'UserAddressKey', 'DestAddressKey'
        )
            ->orderby('task.id', 'desc')
            ->first();

        return $row;
    }

}
