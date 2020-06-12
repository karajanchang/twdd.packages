<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Twdd\Facades\TaskService;

trait TraitHaveNoRuningTask
{
    /*
     * 檢查有沒有進行中任務
     */
    protected function HaveNoRuningTask() : bool{
        //4.檢查有沒有進行中任務
        $res = TaskService::task()->checkNotHaveInProcessTaskStateByMember($this->member);
        if($res!==true){

            //return $this->error($res->getMessage(), $res);
            return false;
        }

        return true;
    }
}
