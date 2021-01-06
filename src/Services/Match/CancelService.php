<?php


namespace Twdd\Services\Match;


use Illuminate\Database\Eloquent\Model;
use Twdd\Services\Match\CancelBy\CarFactory;
use Twdd\Services\Match\CancelBy\Driver;
use Twdd\Services\Match\CancelBy\Member;
use Twdd\Services\Match\CancelBy\User;
use Twdd\Services\ServiceAbstract;

class CancelService extends ServiceAbstract
{
    private $who;
    private $calldriverTaskMap;
    private $task;
    private $maps = [
        'member' => Member::class,
        'driver' => Driver::class,
        'user' => User::class,
        'car_factoris' => CarFactory::class,
    ];

    public function by(Model $who){
        $this->who = $this->maps[$who->getTable()];

        return $this;
    }

    public function calldriverTaskMap(Model $map){
        if($map->getTable()!='calldriver_task_map'){
           throw new \Exception('請放入calldriver_task_map的model');
        }
        $this->calldriverTaskMap = $map;


        return $this;
    }

    public function task(Model $task){
        if($task->getTable()!='task'){
            throw new \Exception('請放入task的model');
        }
        $this->task = $task;

        return $this;
    }

    public function check(){
        $job = app($this->who)->calldriverTaskMap($this->calldriverTaskMap)->task($this->task);
        $res = $job->check();
        if($res===true) {

            return $job;
        }

        return $res;
    }

    public function cancel(array $params, bool $is_force_cancel = false, bool $is_cancel_with_check = false){
        $job = app($this->who)->calldriverTaskMap($this->calldriverTaskMap)->task($this->task);
        if($is_cancel_with_check===true) {

            return $job->cancelWithCheck($params, $is_force_cancel);
        }

        return $job->cancel($params, $is_force_cancel);
    }
}