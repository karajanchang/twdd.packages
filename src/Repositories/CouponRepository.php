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
}
