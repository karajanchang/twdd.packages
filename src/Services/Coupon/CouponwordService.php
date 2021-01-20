<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-14
 * Time: 12:25
 */
namespace Twdd\Services\Coupon;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Database\Eloquent\Model;
use Twdd\Errors\CouponErrors;
use Twdd\Repositories\CouponwordRepository;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\TaskRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class CouponwordService extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $couponRepository = null;
    private $driverRepository = null;
    private $taskRepository = null;

    public function __construct(CouponwordRepository $repository, CouponErrors $error, DriverRepository $driverRepository, TaskRepository $taskRepository)
    {
        $this->driverRepository = $driverRepository;
        $this->error = $error;
        $this->repository = $repository;
        $this->taskRepository = $taskRepository;
    }

    public function fetch($code){
        $couponword = $this->repository->fetch($code);

        if(!isset($couponword->id)){

            return $this->error->_('4001');
        }

        return $couponword;
    }

    //--叩除這次的任務的nums7
    private function nums7ExceptTaskByMemberAndTask(Model $member, Model $task = null){
        if(!empty($task->TaskState) && $task->TaskState==7){

            return $member->nums7 - 1;
        }

        return $member->nums7;
    }

    public function check($code, Model $member = null, Model $task = null){
        $couponword = $this->repository->fetch($code);

        if(!isset($couponword->id)){

            return $this->error->_('4001');
        }

        $now = time();
        if($now < $couponword->startTS || $now > $couponword->endTS){

            return $this->error->_('4003');
        }

        $task_id = empty($task->id) ? null : $task->id;
        if(isset($member->id) && $member->id>0){
            $nums7 = $this->nums7ExceptTaskByMemberAndTask($member, $task);
            if($couponword->only_first_use==1 && $nums7>0){

                return $this->error->_('4004');
            }

            $nums = $this->taskRepository->nums7ByUserCreditCodeAndMemberId($code, $member->id, $task_id);

            if($nums>0 && !($couponword->is_reuse ?? 0)){

                return $this->error->_('4007');
            }


        }

        if(($couponword->nums ?? 0) > 0){
            $nums = $this->taskRepository->nums7ByUserCreditCode($code, $task_id);

            if($nums >= $couponword->nums){

                return $this->error->_('4009');
            }
        }

        return $couponword;
    }

    public function find($id){
        $couponword = $this->repository->find($id);
        if(!isset($couponword->id)){

            return $this->error->_('4001');
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

            return $this->error->_('500');
        }

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
