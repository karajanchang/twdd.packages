<?php

namespace Twdd\Services\Task;

use App\User;
use Illuminate\Support\Facades\Log;
use Twdd\Errors\CallErrors;
use Twdd\Errors\MemberErrors;
use Twdd\Errors\TaskErrors;
use Twdd\Events\CancelCall;
use Twdd\Events\CancelTask;
use Twdd\Events\DriverOffline;
use Twdd\Events\DriverOnline;
use Twdd\Models\CalldriverTaskMap;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class LastCall extends ServiceAbstract
{
    use AttributesArrayTrait;

    private $callError;
    private $mapRepository;
    private $member;
    private $memberError;
    private $taskError;
    private $user;

    public function __construct(CalldriverTaskMapRepository $mapRepository, MemberErrors $memberError, CallErrors $callError, TaskErrors $taskError)
    {
        $this->mapRepository = $mapRepository;
        $this->memberError = $memberError;
        $this->callError = $callError;
        $this->taskError = $taskError;
    }


    /**
     * @return mixed
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param mixed $member
     */
    public function setMember($member)
    {
        $this->member = $member;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }


    public function cancel(int $user_cancel_reason_id = 0){
        if(!isset($this->member->id)){

            return $this->memberError['2003'];
        }
        $call = $this->mapRepository->LastCall($this->member, ['user' => $this->user ]);
        if(!isset($call->id)){

            return $this->callError['2003'];
        }
        Log::info('LastCall:', [$call]);

        //--此呼叫已媒合失敗，不需要取消
        if($call->IsMatchFail==1){

            return $this->callError['1001'];
        }
        //--此呼叫已取消，不需要重覆取消
        if($call->is_cancel==1){

            return $this->callError['1002'];
        }
        //---取消call event
        event(new CancelCall($call));

        //--已開車
        if(isset($call->task)){
            $task = $call->task;
            if($task->TaskState==-1 ) {
                
                return $this->taskError['1007'];
            }
            if($task->TaskState>=4 && $task->TaskState<7 ) {
                
                return $this->taskError['1006'];
            }

            //---取消Task event
            event(new CancelTask($task, $user_cancel_reason_id));

            //--司機下線
            event(new DriverOffline($task->driver));
        }

        return $call;
    }



}