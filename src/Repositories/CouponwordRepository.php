<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Twdd\Criterias\Coupon\WhereCode;
use Twdd\Models\Couponword;
use Zhyu\Repositories\Eloquents\Repository;

class CouponwordRepository extends Repository
{
    public function model(){
        
        return Couponword::class;
    }

    public function fetch($code){

        return $this->findBy('code', $code);
    }
}
