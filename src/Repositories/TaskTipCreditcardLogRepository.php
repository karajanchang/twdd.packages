<?php

namespace Twdd\Repositories;


use Twdd\Models\TaskTip;
use Twdd\Models\TaskTipCreditcardLog;
use Zhyu\Repositories\Eloquents\Repository;

class TaskTipCreditcardLogRepository extends Repository
{
    public function model(){

        return TaskTipCreditcardLog::class;
    }
}
