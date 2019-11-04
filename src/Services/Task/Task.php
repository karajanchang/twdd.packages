<?php


namespace Twdd\Services\Task;


use Illuminate\Database\Eloquent\Model;
use Twdd\Errors\TaskErrors;
use Twdd\Facades\TwddCache;
use Twdd\Repositories\TaskRepository;
use Twdd\Traits\ModelToolTrait;

class Task
{
    use ModelToolTrait;

    private $error;
    private $repository;

    /**
     * Task constructor.
     */
    public function __construct(TaskRepository $repository, TaskErrors $taskErrors)
    {
        $this->repository = $repository;
        $this->error = $taskErrors;
    }

    public function profile(int $id, array $columns = ['*'], $clear_cache = false){
        $default_profile = TwddCache::task($id)->key('Task', $id)->get();

        if(!$default_profile || $clear_cache===true){
            $default_profile = $this->repository->find($id);
            TwddCache::task($id)->key('Task', $id)->put($default_profile);
        }

        $all_columns = ['*'];
        if(count(array_diff($columns, $all_columns))==0){

            return $default_profile;
        }

        if($this->checkColumnsIsExistsInThisModel($columns, $default_profile)===false){

            return $this->repository->find($id, $columns);
        }

        return $default_profile;
    }

    //---檢查該會員有沒有進行中 0-6 的任務
    public function checkNotHaveInProcessTaskStateByMember(Model $member){
        if($this->repository->checkNotHaveInProcessTaskByMemberId($member->id)===true){

            return $this->error['1008'];
        }

        return true;
    }

    //---檢查該司機有沒有進行中 0-6 的任務
    public function checkNotHaveInProcessTaskStateByDriver(Model $driver){
        if($this->repository->checkNotHaveInProcessTaskByDriverId($driver->id)===true){

            return $this->error['1008'];
        }

        return true;
    }

}
