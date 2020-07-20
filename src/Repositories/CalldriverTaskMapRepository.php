<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Twdd\Criterias\Calldriver\JoinCalldriver;
use Twdd\Criterias\Calldriver\OrderByMapId;
use Twdd\Criterias\Calldriver\WhereIsCancelOrIsMatchFail;
use Twdd\Criterias\Calldriver\WhereMember;
use Twdd\Criterias\Calldriver\WhereTSOver;
use Twdd\Criterias\Calldriver\WhereUser;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Models\Member;
use Zhyu\Repositories\Eloquents\Repository;

class CalldriverTaskMapRepository extends Repository
{
    public function model(){

        return CalldriverTaskMap::class;
    }

    public function numsOfDuplcateByMember(Model $member){
        $joinCalldriver = new JoinCalldriver();
        $whereMember = new WhereMember($member);
        $whereTSOver = new WhereTSOver();
        $whereIsCancelOrIsMatchFail = new WhereIsCancelOrIsMatchFail();

        $this->pushCriteria($joinCalldriver);
        $this->pushCriteria($whereMember);
        $this->pushCriteria($whereTSOver);
        $this->pushCriteria($whereIsCancelOrIsMatchFail);

        $count = $this->count();

        return $count;
    }

    public function currentCall(int $calldriver_id){
        $joinCalldriver = new JoinCalldriver();

        $this->pushCriteria($joinCalldriver);

        $call = $this->findBy('calldriver_id', $calldriver_id, [ 'calldriver_task_map.id as id', 'calldriver_id', 'calldriver_task_map.task_id', 'calldriver_task_map.driver_id',
            'IsMatchFail', 'addr', 'addrKey', 'addr_det', 'addrKey_det', 'lat', 'lon', 'UserCreditCode', 'UserCreditValue' ]);

        return $call;
    }

    public function lastCall(Member $member, array $params = []){
        $joinCalldriver = new JoinCalldriver();
        $orderByMapId = new OrderByMapId('desc');

        $this->pushCriteria($joinCalldriver);
        $this->pushCriteria($orderByMapId);
        if(isset($params['user']) && $params['user'] instanceof User){
            $whereUser = new WhereUser($params['user']);
            $this->pushCriteria($whereUser);
        }

        $call = $this->findBy('calldriver_task_map.member_id', $member->id, [ 'calldriver_task_map.id as id', 'calldriver_id', 'calldriver_task_map.TS', 'calldriver_task_map.task_id', 'calldriver_task_map.driver_id',
            'IsMatchFail', 'addr', 'addrKey', 'addr_det', 'addrKey_det', 'lat', 'lon', 'UserCreditCode', 'UserCreditValue', 'is_done', 'is_cancel', 'calldriver_task_map.member_id', 'calldriver_task_map.is_push' ]);


        return $call;
    }

    /*
     * 檢查此司機幾秒內是否有媒合的單
     */
    public function isInMatchingByDriverID(int $driver_id, int $seconds = 45) : bool{
        $count = $this->where('driver_id', $driver_id)->where(DB::raw('UNIX_TIMESTAMP() - TS'), '<', $seconds)->count();

        return $count > 0;
    }

    /*
     * 在 指定時間內有預約單的數量
     */
    public function numsOfPrematchByMemberIdAndHour(int $member_id, float $hour) : int{
        $now = Carbon::now();

        return $this->where('member_id', $member_id)
            ->whereBetween('TS', [$now->timestamp, $now->addMinutes($hour*60)->timestamp])
            ->where('call_type', 2)
            ->where('is_done', 1)
            ->where('is_cancel', 0)
            ->where('IsMatchFail', 0)
            ->count();
    }
}
