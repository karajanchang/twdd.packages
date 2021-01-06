<?php


namespace Twdd\Repositories;


use Twdd\Models\TaskCancelLog;
use Zhyu\Repositories\Eloquents\Repository;

class TaskCancelLogRepository extends Repository
{
    public function model()
    {
        return TaskCancelLog::class;
    }

    
}