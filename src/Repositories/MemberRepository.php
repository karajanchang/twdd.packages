<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Twdd\Criterias\WhereUserPhone;
use Twdd\Models\Member;
use Zhyu\Repositories\Eloquents\Repository;

class MemberRepository extends Repository
{

    public function model(){
        return Member::class;
    }

    public function countByUserPhone($UserPhone){
        $userPhoneCriteria = new WhereUserPhone($UserPhone);
        $this->pushCriteria($userPhoneCriteria);
        return $this->count();
    }

    public function byInviteCode(string $OtherInviteCode){

        return $this->where('InviteCode', $OtherInviteCode)->first();
    }
}