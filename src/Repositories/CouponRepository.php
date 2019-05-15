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
}
