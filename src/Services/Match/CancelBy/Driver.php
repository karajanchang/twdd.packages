<?php


namespace Twdd\Services\Match\CancelBy;


use Twdd\Repositories\TaskRepository;

class Driver implements InterfaceCancelBy
{
    use TraitCancelBy;
    private $cancel_by = 2;


    public function cancelCalldriverTaskMap(array $params = null){
        $this->calldriverTaskMap->is_cancel = 1;
        $this->calldriverTaskMap->save();

        //--如果是車廠，把有關連的map也取消
        $cancel_reason_id = $params['cancel_reason_id'] ?? null;
        $this->cancelOtherMap($cancel_reason_id);
    }

    public function cancelTask(array $params = null){
        $all = [
            'TaskState' => -1,
            'isCancelByDriver' => 1,
            'TaskCancelTS' => time(),
            'user_cancel_reason_id' => $params['cancel_reason_id'] ?? null,

        ];
        app(TaskRepository::class)->where('id', $this->task->id)->update($all);

        //--把coupon還給用戶，但預約call_type=2的要還
        $this->unUsedCoupon();
    }

    public function processParams(array $params){

        return [
            'task_id' => $this->task->id ?? null,
            'cancel_by' => $this->cancel_by,
            'cancel_reason_id' => $params['user_cancel_reason_id'] ?? null,
            'cancel_reason' => $params['TaskCancelReason'] ?? null,
            'cancel_fee' => 0,
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
