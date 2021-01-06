<?php


namespace Twdd\Services\Match\CancelBy;


class CarFactory implements InterfaceCancelBy
{
    use TraitCancelBy;
    private $cancel_by = 4;

    public function cancelCalldriverTaskMap(array $params = null){
        $this->calldriverTaskMap->IsMatchFail = 1;
        $this->calldriverTaskMap->cancel_by = $this->cancel_by;
        $this->calldriverTaskMap->cancel_reason_id = $params['cancel_reason_id'];

        $this->calldriverTaskMap->save();

        $calldriver = $this->calldriverTaskMap->calldriver;
        $calldriver->IsMatch = 0;
        $calldriver->save();
    }

    public function cancelTask(array $params = null){
        $this->task->TaskState = -1;

        $this->task->isCancelByService = 1;
        $this->task->TaskCancelTS = time();
        $this->task->is_admin_edit = 1;

        $this->task->save();
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

        return false;
    }
}