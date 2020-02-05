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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Twdd\Errors\CouponErrors;
use Twdd\Repositories\CouponRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class CouponService extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $member = null;
    private $members = [];
    private $user;
    private $couponCode;
    private $codes = [];


    public function __construct(CouponRepository $repository, CouponErrors $error, CouponCode $couponCode)
    {
        $this->repository = $repository;
        $this->error = $error;
        $this->couponCode = $couponCode;
    }

    public function check($code, Model $member = null, Model $task = null){
        $coupon = $this->repository->fetch($code);
        //dd($coupon);
        if(!isset($coupon->id)){

            Log::info('CouponService '.$code.' check: ', [$coupon]);
            return $this->error->_('4001');
        }

        if($coupon->isUsed==1){

            return $this->error->_('4002');
        }

        $now = time();
        if($now < $coupon->startTS || $now > $coupon->endTS){

            return $this->error->_('4003');
        }

        $nums7_except_this_task = $this->nums7ExceptTaskByMemberAndTask($member, $task);
        if($coupon->only_first_use==1 && isset($member->id) && $member->id>0 && $nums7_except_this_task>0){

            return $this->error->_('4004');
        }

        if ($coupon->isOpen == 0) {

            return $this->error->_('4005');
        }

        if ($coupon->isOnlyForThisMember == 1 && isset($member->id) && $member->id!= $coupon->member_id) {

            return $this->error->_('4006');
        }

        return $coupon;
    }

    //--叩除這次的任務的nums7
    private function nums7ExceptTaskByMemberAndTask(Model $member, Model $task = null){
        if(!empty($task->TaskState) && $task->TaskState==7){

            return $member->nums7 - 1;
        }

        return $member->nums7;
    }

    public function validCouponword($code, Model $member, Model $task = null){
        $coupon = $this->repository->firstByCodeAndMember($code, $member->id);

        if(!isset($coupon->id)){

            Log::info('CouponService '.$code.' validCouponword: ', [$coupon]);
            return $this->error->_('4001');
        }

        if($coupon->isUsed==1){

            return $this->error->_('4002');
        }

        $now = time();
        if($now < $coupon->startTS || $now > $coupon->endTS){

            return $this->error->_('4003');
        }

        $nums7_except_this_task = $this->nums7ExceptTaskByMemberAndTask($member, $task);

        if($coupon->only_first_use==1 && isset($member->id) && $member->id>0 && $nums7_except_this_task>0){

            return $this->error->_('4004');
        }

        if ($coupon->isOpen == 0) {

            return $this->error->_('4005');
        }

        if ($coupon->isOnlyForThisMember == 1 && isset($member->id) && $member->id!= $coupon->member_id) {

            return $this->error->_('4006');
        }

        return $coupon;
    }

    public function create(array $params){
        $error = $this->validate($params);
        if($error!==true){
            return $error;
        }

        try {
            if(!is_null($this->member)){

                return $this->createSingle($params);
            }else {

                return $this->createBatch($params);
            }
        }catch(\Exception $e){
            Bugsnag::notifyException($e);

            return $this->error->_('500');
        }

    }

    public function createByArray(array $params_array){
        $new_params_array = array_map(function ($params){
            $code = $this->genCode();
            $params['code'] = $code;

            return $params;
        }, $params_array);

        $res = $this->repository->insert($new_params_array);

        return $res;
    }

    private function createSingle($params){
        $params['member_id'] = isset($this->member->id) ? $this->member->id : null;
        $params['mobile'] = isset($this->member->UserPhone) ? $this->member->UserPhone : null;
        if(!isset($params['code']) || strlen($params['code'])==0) {
            $code = $this->genCode();
            $params['code'] = $code;
        }
        $coupon = $this->repository->create($params);

        return $coupon;
    }

    private function genCode(){
        $code = $this->couponCode->init();
        if(!in_array($code, $this->codes)){
            $this->codes[] = $code;

            return $code;
        }

        return $this->genCode();
    }

    private function createBatch($params){
        $chunks = array_chunk($this->members, 500);
        $nums = 0;
        foreach ($chunks as $members) {
            $params_array = array_map(function ($member) use ($params, &$nums) {

                $code = $this->genCode();
                $params['code'] = $code;
                $params = $this->filter($params);
                $params['member_id'] = isset($member->id) ? $member->id : null;
                $params['mobile'] = isset($member->UserPhone) ? $member->UserPhone : null;

                $nums++;

                return $params;
            }, $members);

            $this->repository->insert($params_array);
        }

        //---empty array
        $this->members = [];

        return $nums;
    }

    public function member(Model $member = null){
        $this->member = $member;

        return $this;
    }


    public function members(array $members){
        if(!is_null($this->member) && count($this->members)){
            throw new \Exception('only one method to use!');
        }
        $this->members = $members;

        return $this;
    }



    public function user(User $user = null){
        $this->user = $user;

        return $this;
    }

    private function filter(array $params){
        $params['user_id'] = isset($this->user->id) ? $this->user->id : null;
        $params['createtime'] = date('Y-m-d H:i:s');
        $params['isUsed'] = 0;
        $params['is_click'] = 0;
        $params['type'] = 1;
        $params['isOnlyForThisMember'] = isset($params['is_only_for_this_member']) ? $params['is_only_for_this_member'] : 0;
        UNSET($params['is_only_for_this_member']);

        return $params;
    }

    public function rules(){

        return [
            //'code' => 'required|string',
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
