<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-16
 * Time: 11:43
 */

namespace Twdd\Services\Task;


class TaskNo
{
    public static function make(int $task_id){

        return str_pad($task_id, 8, '0', STR_PAD_LEFT);
    }

}
