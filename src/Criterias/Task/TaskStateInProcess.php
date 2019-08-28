<?php
namespace Twdd\Criterias\Task;

use Zhyu\Repositories\Contracts\RepositoryInterface;
use Zhyu\Repositories\Criterias\Criteria;

class TaskStateInProcess extends Criteria
{

    public function apply($model, RepositoryInterface $repository){
        $model = $model->whereBetween('task.TaskState', [0, 6]);

        return $model;
    }
}
