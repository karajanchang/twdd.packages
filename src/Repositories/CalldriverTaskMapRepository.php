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
use Twdd\Criterias\Calldriver\WherePayTypeNot4;
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
        $wherePayTypeNot4 = new WherePayTypeNot4();

        $this->pushCriteria($joinCalldriver);
        $this->pushCriteria($whereMember);
        $this->pushCriteria($whereTSOver);
        $this->pushCriteria($whereIsCancelOrIsMatchFail);
        $this->pushCriteria($wherePayTypeNot4);

        $count = $this->count();

        return $count;
    }

    public function currentCall(int $calldriver_id){
        $joinCalldriver = new JoinCalldriver();

        $this->pushCriteria($joinCalldriver);

        $call = $this->findBy('calldriver_id', $calldriver_id, [ 'calldriver_task_map.id as id', 'calldriver_id', 'calldriver_task_map.task_id', 'calldriver_task_map.driver_id', 'calldriver_task_map.member_id',
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
     * 檢查此駕駛幾秒內是否有媒合的單
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

    /*
     * 取得該會員預約中的map
     */
    public function prematchByCalldriverId(int $calldriver_id){

        return $this->join('calldriver', 'calldriver_task_map.calldriver_id', '=', 'calldriver.id')
            ->leftJoin('driver', 'calldriver_task_map.driver_id', '=', 'driver.id')
            ->leftJoin('member', 'calldriver_task_map.member_id', '=', 'member.id')
            ->leftJoin('task', 'calldriver_task_map.task_id', '=', 'task.id')
            ->where('calldriver.IsDelete', '!=', 1)
            ->where('calldriver_task_map.is_cancel', 0)
            ->where('calldriver_task_map.is_done', 0)
            ->where('calldriver_task_map.IsMatchFail', 0)
            ->where('calldriver_task_map.call_type', 2)
            ->where('calldriver_task_map.calldriver_id', $calldriver_id)
            //->where('calldriver_task_map.is_push', 1)
            ->select('calldriver_task_map.is_done', 'calldriver.id', DB::raw('calldriver_task_map.id as map_id'), 'calldriver_id','driver.DriverName', 'driver.DriverID', 'driver.DriverRating', 'driver.DriverPhoto', 'driver.DriverServiceTime', 'calldriver.UserRemark', 'calldriver.lat', 'calldriver.lon', 'calldriver.city', 'calldriver.district', 'calldriver.addr','calldriver.lat_det', 'calldriver.lon_det', 'calldriver.city_det', 'calldriver.district_det', 'calldriver.addr_det', 'calldriver.addrKey', 'calldriver.addrKey_det',
                'calldriver_task_map.driver_id', 'calldriver_task_map.task_id', 'calldriver_task_map.is_push', 'calldriver_task_map.user_cancel_reason_id', 'calldriver.TS', DB::raw('CEILING((UNIX_TIMESTAMP()-UNIX_TIMESTAMP(Driver.DriverDrivingSeniorityDate))/3600/24/365) as DriverDrivingSeniorityYear'), 'DriverDrivingSeniorityDate', 'task.TaskState', 'calldriver_task_map.IsMatchFail', 'calldriver.createtime' , 'calldriver_task_map.is_cancel', 'calldriver.type', 'calldriver.pay_type',
                'calldriver.call_type', 'calldriver_task_map.member_id', 'calldriver.UserCreditCode', 'calldriver.UserCreditValue', DB::raw('calldriver.addrKey as UserAddressKey'), DB::raw('calldriver.addrKey_det as DestAddressKey'), 'calldriver.extra_price', 'calldriver.zip', 'calldriver.zip_det', 'calldriver.DeviceTypeMember', 'calldriver.AppVerMember', 'calldriver.OSVerMember', 'calldriver.DeviceModelMember', 'calldriver.user_id', 'calldriver.callback_url', 'calldriver.IsApi', DB::raw('left(member.UserName, 1) as UserName')
            )
            ->get();
    }

    /*
     * 從task_id去拿到calldriverTaskMap
     */
    public function firstFromTaskId(int $task_id){

        return $this->where('task_id', $task_id)->first();
    }

    /**
     *
     * @param int $calldriver_id
     * @param int $except_map_id
     * @param int $cancel_by
     * @param int|null $cancel_reason_id
     */
    public function cancelOtherSameCalldriverId(int $calldriver_id, int $except_map_id, int $cancel_by, int $cancel_reason_id = null){

        $this->where('calldriver_id', $calldriver_id)
            ->where('is_cancel', 0)
            ->where('id', '!=', $except_map_id)
            ->update([
                'is_cancel' => 1,
                'cancel_by' => $cancel_by,
                'cancel_reason_id' => $cancel_reason_id,
            ]);
    }

}
