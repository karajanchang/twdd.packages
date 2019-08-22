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
use Twdd\Models\Member;

class CouponFactory
{
    private $member = null;
    private $members = [];
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

    public function addMember(\stdClass $member){
        if(!is_null($this->member)){
            throw new \Exception('only one method to use!');
        }

        if(!($member instanceof Member) && !($member instanceof \App\Member)){
            $member->id = $member->member_id;
        }
        array_push($this->members, $member);

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

        $res = $app->init($this);

        $this->member = null;
        $this->members = [];
        return $res;
    }

    public function createByArray(array $params_array){
        $app = app()->make($this->createClass);

        $res = $app->initByArray($this, $params_array);
        return $res;
    }

    public function __get($name){

        return $this->$name;
    }
}
