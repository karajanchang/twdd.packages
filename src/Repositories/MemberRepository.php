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

    public function byInviteCode(string $OtherInviteCode, array $columns = ['*']){

        return $this->where('InviteCode', $OtherInviteCode)->select($columns)->first();
    }

    public function profile(int $id, array $columns = []){
        $rcolumns = [
            'member_grade_id', 'UserName', 'UserName', 'UserPhone', 'UserEmail', 'UserGender', 'InviteCode', 'OtherInviteCode',
            'is_online', 'is_mobile_verify', 'is_registered',
            'UserEmergencyName', 'UserEmergencyPhone', 'UserEmergencyGender',
            'nums7', 'numsFail', 'numsBy7', 'numsByFail', 'pay_type',
        ];
        if(count($columns)){
            $rcolumns = $columns;
        }

        return $this->find($id, $rcolumns);
    }
}