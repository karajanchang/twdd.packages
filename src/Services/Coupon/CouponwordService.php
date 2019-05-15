<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-14
 * Time: 12:25
 */
namespace Twdd\Services\Coupon;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Twdd\Errors\CouponErrors;
use Twdd\Models\InterfaceModel;
use Twdd\Repositories\CouponwordRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class CouponwordService extends ServiceAbstract
{
    use AttributesArrayTrait;

    public function __construct(CouponwordRepository $repository, CouponErrors $error)
    {
        $this->repository = $repository;
        $this->error = $error;
    }

    public function valid(InterfaceModel $member, $code){
        $couponword = $this->repository->fetch($code);

        if(!isset($couponword->id)){

            return $this->error['4001'];
        }

        $now = time();
        if($couponword->startTS<$now || $now > $couponword->endTS){

            return $this->error['4003'];
        }

        return $couponword;
    }

    public function find($id){
        $couponword = $this->repository->find($id);
        if(!isset($couponword->id)){
            
            return $this->error['4001'];
        }

        return $couponword;
    }

    public function create(array $params){
        $error = $this->validate($params);
        if($error!==true){
            return $error;
        }

        try {
            $params = $this->filter($params);

            $coupon = $this->repository->create($params);

            return $coupon;
        }catch(\Exception $e){
            Bugsnag::notifyException($e);

            return $this->error['500'];
        }

        return $this->error['500'];
    }

    private function filter(array $params){
        $params['createtime'] = date('Y-m-d H:i:s');

        return $params;
    }

    public function rules(){
        return [
            'code' => 'required|string',
            'money' => 'required|integer|max:500',
            'title' => 'required|string',
            'startTS' => 'required',
            'endTS' => 'required',
            'only_first_use' => 'required|integer|max:1',
            'user_id' => 'nullable|integer',
            'activity_id' => 'nullable|integer',
        ];
    }
}
