<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-14
 * Time: 12:25
 */
namespace Twdd\Services\Coupon;

use App\User;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Twdd\Errors\CouponErrors;
use Twdd\Models\InterfaceModel;
use Twdd\Repositories\CouponRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class CouponService extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $member;
    private $user;

    public function __construct(CouponRepository $repository, CouponErrors $error)
    {
        $this->repository = $repository;
        $this->error = $error;
    }

    public function valid($code){
        $coupon = $this->repository->fetch($code);

        if(!isset($coupon->id)){

            return $this->error['4001'];
        }

        if($coupon->isUsed==1){

            return $this->error['4002'];
        }

        $now = time();
        if($coupon->startTS<$now || $now > $coupon->endTS){

            return $this->error['4003'];
        }

        return $coupon;
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

    public function member(InterfaceModel $member){
        $this->member = $member;

        return $this;
    }

    public function user(User $user){
        $this->user = $user;

        return $this;
    }

    private function filter(array $params){
        $params['member_id'] = isset($this->member->id) ? $this->member->id : null;
        $params['mobile'] = isset($this->member->UserPhone) ? $this->member->UserPhone : null;

        $params['user_id'] = isset($this->user->id) ? $this->user->id : null;
        $params['createtime'] = date('Y-m-d H:i:s');
        $params['isUsed'] = 0;
        $params['is_click'] = 0;
        $params['type'] = 1;
        $params['isOnlyForThisMember'] = isset($params['is_only_for_this_member']) ? $params['is_only_for_this_member'] : 0;

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
            'queue_id' => 'nullable|integer',
            'coupon_queue_id' => 'nullable|integer',
            'store_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'activity_id' => 'nullable|integer',
            'is_only_for_this_member' => 'nullable|integer|max:1',
            'sno' => 'nullable|integer',
        ];
    }
}
