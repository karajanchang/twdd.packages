<?php


namespace Twdd\Services\Match\CancelBy;


use Twdd\Repositories\TaskRepository;

class Member implements InterfaceCancelBy
{
    use TraitCancelBy;
    private $cancel_by = 1;


    public function cancelCalldriverTaskMap(array $params = null){
        $this->calldriverTaskMap->is_cancel = 1;
        $this->calldriverTaskMap->user_cancel_reason_id = $params['cancel_reason_id'] ?? null;
        $this->calldriverTaskMap->save();

        //--如果是車廠，把有關連的map也取消
        $cancel_reason_id = $params['cancel_reason_id'] ?? null;
        $this->cancelOtherMap($cancel_reason_id);
    }

    public function cancelTask(array $params = null){
        $all = [
            'TaskState' => -1,
            'isCancelByUser' => 1,
            'TaskCancelTS' => time(),
            'user_cancel_reason_id' => $params['cancel_reason_id'] ?? null,

        ];

        //--若要收取違約取消費
        if($this->do_not_charge_cancel_fee===false  && $this->fees['TaskFee'] > 0) {
            $all['TaskState'] = 7;
            $all['user_violation_id'] = $this->fees['user_violation_id'];
            $all['TaskFee'] = $this->fees['TaskFee'];
            $all['twddFee'] = $this->fees['twddFee'];
        }
        app(TaskRepository::class)->where('id', $this->task->id)->update($all);

        if(!empty($this->task->UserCreditCode) && isset($this->calldriverTaskMap->call_type) && $this->calldriverTaskMap->call_type==2){
            $this->unUsedCoupon();
        }
    }

    public function processParams(array $params){
        $cancel_reason_id = isset($params['cancel_reason_id']) ? $params['cancel_reason_id'] : ($params['user_cancel_reason_id'] ?? null);

        return [
            'task_id' => $this->task->id ?? null,
            'cancel_by' => $this->cancel_by,
            'cancel_reason_id' => $cancel_reason_id,
            'cancel_reason' => $params['cancel_reason'] ?? null,
            'cancel_fee' => $this->fees['TaskFee'],
        ];
    }

    //--檢查是否可以取消
    public function check(){
        if(isset($this->task->TaskState) && $this->task->TaskState >=4 ){

            return false;
        }

        return true;
    }
}
