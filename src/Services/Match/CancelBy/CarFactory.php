<?php


namespace Twdd\Services\Match\CancelBy;


use Twdd\Repositories\TaskRepository;

class CarFactory implements InterfaceCancelBy
{
    use TraitCancelBy;
    private $cancel_by = 4;

    public function cancelCalldriverTaskMap(array $params = null){
        $this->calldriverTaskMap->IsMatchFail = 1;
        $this->calldriverTaskMap->cancel_by = $this->cancel_by;
        $this->calldriverTaskMap->cancel_reason_id = $params['cancel_reason_id'] ?? null;

        $this->calldriverTaskMap->save();

        $calldriver = $this->calldriverTaskMap->calldriver;
        $calldriver->IsMatch = 0;
        $calldriver->save();
    }

    public function cancelTask(array $params = null){
        $all = [
            'TaskState' => -1,
            'isCancelByService' => 1,
            'TaskCancelTS' => time(),
            'is_admin_edit' => 1,

        ];
        app(TaskRepository::class)->where('id', $this->task->id)->update($all);
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