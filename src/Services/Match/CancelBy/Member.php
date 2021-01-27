<?php


namespace Twdd\Services\Match\CancelBy;


class Member implements InterfaceCancelBy
{
    use TraitCancelBy;
    private $cancel_by = 1;


    public function cancelCalldriverTaskMap(array $params = null){
        $this->calldriverTaskMap->is_cancel = 1;
        $this->calldriverTaskMap->user_cancel_reason_id = $params['user_cancel_reason_id'];
        $this->calldriverTaskMap->save();
    }

    public function cancelTask(array $params = null){
        $this->task->TaskState = -1;

        $this->task->isCancelByUser = 1;
        $this->task->TaskCancelTS = time();
        $this->task->user_cancel_reason_id = $params['user_cancel_reason_id'] ?? null;

        $this->task->save();

        if(!empty($this->task->UserCreditCode) && isset($this->calldriverTaskMap->call_type) && $this->calldriverTaskMap->call_type==2){
            $this->unUsedCoupon();
        }
    }

    public function processParams(array $params){

        return [
            'task_id' => $this->task->id ?? null,
            'cancel_by' => $this->cancel_by,
            'cancel_reason_id' => $params['user_cancel_reason_id'] ?? null,
            'cancel_reason' => $params['cancel_reason'] ?? null,
        ];
    }

    public function check(){
        if(isset($this->task->TaskState) && $this->task->TaskState >=4 ){

            return false;
        }

        return true;
    }
}