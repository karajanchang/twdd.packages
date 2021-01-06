<?php


namespace Twdd\Services\Match\CancelBy;


class Driver implements InterfaceCancelBy
{
    use TraitCancelBy;
    private $cancel_by = 2;


    public function cancelCalldriverTaskMap(array $params = null){
        $this->calldriverTaskMap->is_cancel = 1;
        $this->calldriverTaskMap->save();
    }

    public function cancelTask(array $params = null){
        $this->task->TaskState = -1;

        $this->task->isCancelByDriver = 1;
        $this->task->TaskCancelTS = time();

        $this->task->save();

        //--把coupon還給用戶，但預約call_type=2的要還
        $this->unUsedCoupon();
    }

    public function processParams(array $params){

        return [
            'task_id' => $this->task->id ?? null,
            'cancel_by' => $this->cancel_by,
            'cancel_reason_id' => $params['user_cancel_reason_id'] ?? null,
            'cancel_reason' => $params['TaskCancelReason'] ?? null,
        ];
    }

    public function check(){

    }
}