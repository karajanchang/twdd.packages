<?php

namespace Twdd\Repositories;


use Twdd\Models\TaskTip;
use Zhyu\Repositories\Eloquents\Repository;

class TaskTipRepository extends Repository
{
    public function model(){

        return TaskTip::class;
    }
}
