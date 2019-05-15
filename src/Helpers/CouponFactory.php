<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 11:32
 */

namespace Twdd\Helpers;


use App\User;
use Illuminate\Support\Collection;
use Twdd\Models\InterfaceModel;

class CouponFactory
{
    private $member = null;
    private $user = null;
    private $params = [];
    private $createClass;

    private $maps = [
        'couponword' => \Twdd\Services\Coupon\Batch\CouponBatchFromCouponword::class,
        'coupon' => \Twdd\Services\Coupon\Batch\CouponBatch::class,
    ];

    public function type($type = 'coupon'){
        $lut = new Collection($this->maps);
        $this->createClass = $lut->get($type);

        return $this;
    }

    public function member(InterfaceModel $member){
        $this->member = $member;

        return $this;
    }

    public function user(User $user){
        $this->user = $user;

        return $this;
    }

    public function params(array $params){
        $this->params = $params;

        return $this;
    }

    public function create(array $params = []){
        if(count($params)){
            $this->params($params);
        }

        $app = app()->make($this->createClass);

        return $app->init($this);
    }

    public function __get($name){

        return $this->$name;
    }
}
