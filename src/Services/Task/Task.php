<?php


namespace Twdd\Services\Task;


use Twdd\Errors\TaskErrors;
use Twdd\Models\Member;
use Twdd\Repositories\TaskRepository;

class Task
{

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

    //---檢查沒有進行中 0-6 的任務
    public function checkNotHaveInPrcessTaskStateByMember(Member $member){
        if($this->repository->checkNotHaveInProcessTaskByMemberId($member->id)===true){

            return $this->error['1008'];
        }

        return true;
    }

}
