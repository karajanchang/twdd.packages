<?php


namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TaskDoneHelper
{
    protected $task;
    protected $member_creditcard_id = 0;


    public function __construct()
    {
        $this->lut = include_once __DIR__.'/../Services/TaskDones/config.php';
    }

    public function task(Model $task, array $pay_result = []){
        $this->task = $task;
        if(isset($pay_result['member_creditcard_id'])) {
            $this->member_creditcard_id = $pay_result['member_creditcard_id'];
        }

        return $this;
    }

    private function determinePayType() : int{
        //---車廠為4
       if(isset($this->task->car_factory_id)){

           return 4;
       }

       return $this->task->pay_type;
    }

    public function done(){

        $taskDone = app(Collection::make($this->lut)->get($this->determinePayType()));
        $taskDone->setTask($this->task, $this->member_creditcard_id);
        $taskDone->done();

        return $this;
    }
}

//---
/*
 * TaskDone::task($task, ['member_creditcard_id' => $member_creditcard_id])->done();
 */