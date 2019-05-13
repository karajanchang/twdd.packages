<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Twdd\Criterias\Calldriver\JoinCalldriver;
use Twdd\Criterias\Calldriver\WhereMember;
use Twdd\Criterias\Calldriver\WhereTSOver;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Models\Member;
use Zhyu\Repositories\Eloquents\Repository;

class CalldriverTaskMapRepository extends Repository
{
    public function model(){
        return CalldriverTaskMap::class;
    }

    public function checkIfDuplcate(Member $member){
        $joinCalldriver = new JoinCalldriver();
        $whereMember = new WhereMember($member);
        $whereTSOver = new WhereTSOver();

        //$this->pushCriteria($joinCalldriver);
        $this->pushCriteria($whereMember);
        $this->pushCriteria($whereTSOver);
        
        return $this->count();
    }

}
