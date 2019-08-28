<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:23
 */

namespace Twdd\Helpers;


use Twdd\Models\Member;
use Twdd\Services\Task\Task;

class TaskService
{
    public function ServiceArea(){
        $app = app()->make(\Twdd\Services\Task\ServerviceArea::class);

        return $app;
    }

    public function calldriver(Member $member){
        $app = app()->make(\Twdd\Services\Task\CalldriverService::class);

        $app->setCallMember($member);

        return $app;
    }

    public function task(){
        $app = app()->make(\Twdd\Services\Task\Task::class);

        return $app;
    }
}
