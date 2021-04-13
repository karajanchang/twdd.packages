<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Twdd\Criterias\Coupon\WhereCode;
use Twdd\Models\Coupon;
use Zhyu\Repositories\Eloquents\Repository;

class CouponRepository extends Repository
{
    public function model(){

        return Coupon::class;
    }

    public function fetch($code){

        return $this->findBy('code', $code);
    }

    //---couponword用
    public function firstByCodeAndMember($code, $member_id){

        return $this->where('member_id', $member_id)->where('code', $code)->orderBy('id', 'desc')->first();
    }

    public function setUsed(int $id, int $member_id){
        $params = [
            'isUsed' => 1,
            'usedtime' => date('Y-m-d H:i:s'),
            'member_id' => $member_id,
        ];

        return $this->update($id, $params);
    }

    /*
     * 返回預約先用掉的coupon
     */
    public function setUnUsed(int $member_id, string $UserCreditCode){

        return $this->where('member_id', $member_id)->where('code', $UserCreditCode)->update([
            'isUsed' => 0,
            'usedtime' => null,
        ]);
    }
}
