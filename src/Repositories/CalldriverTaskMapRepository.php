<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Twdd\Criterias\Calldriver\JoinCalldriver;
use Twdd\Criterias\Calldriver\WhereIsCancelOrIsMatchFail;
use Twdd\Criterias\Calldriver\WhereMember;
use Twdd\Criterias\Calldriver\WhereTSOver;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Models\InterfaceModel;
use Zhyu\Repositories\Eloquents\Repository;

class CalldriverTaskMapRepository extends Repository
{
    public function model(){
        return CalldriverTaskMap::class;
    }

    public function checkIfDuplcate(InterfaceModel $member){
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

}
