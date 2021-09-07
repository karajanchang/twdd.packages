<?php


namespace Twdd\Services\Match\CancelBy;


use Twdd\Repositories\TaskRepository;

class CarFactory implements InterfaceCancelBy
{
    use TraitCancelBy;
    private $cancel_by = 4;

    public function cancelCalldriverTaskMap(array $params = null){
        $this->calldriverTaskMap->IsMatchFail = 0;
        $this->calldriverTaskMap->is_cancel = 1;
        $this->calldriverTaskMap->cancel_by = $this->cancel_by;
        $this->calldriverTaskMap->cancel_reason_id = $params['cancel_reason_id'] ?? null;

        $this->calldriverTaskMap->save();

        $calldriver = $this->calldriverTaskMap->calldriver;
        $calldriver->IsMatch = 0;
        $calldriver->save();

        //--如果是車廠，把有關連的map也取消
        $cancel_reason_id = $params['cancel_reason_id'] ?? null;
        $this->cancelOtherMap($cancel_reason_id);
    }


    public function cancelTask(array $params = null){
        $all = [
            'TaskState' => -1,
            'isCancelByService' => 1,
            'TaskCancelTS' => time(),
        ];

        //--若要收取違約取消費
        if($this->do_not_charge_cancel_fee===false  && $this->fees['TaskFee'] > 0) {
            $all['TaskState'] = 7;
            $all['user_violation_id'] = $this->fees['user_violation_id'];
            $all['TaskFee'] = $this->fees['TaskFee'];
            $all['twddFee'] = $this->fees['twddFee'];
        }
        app(TaskRepository::class)->where('id', $this->task->id)->update($all);
    }

    public function processParams(array $params){

        return [
            'task_id' => $this->task->id ?? null,
            'cancel_by' => $this->cancel_by,
            'cancel_reason_id' => $params['user_cancel_reason_id'] ?? null,
            'cancel_reason' => $params['cancel_reason'] ?? null,
            'cancel_fee' => $this->fees['TaskFee'],
        ];
    }

    //--檢查是否可以取消
    public function check(){

        return true;
    }
}
