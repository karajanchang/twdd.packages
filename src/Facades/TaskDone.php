<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:22
 */

namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class TaskDone extends Facade {
    protected static function getFacadeAccessor() { return 'TaskDone'; }
}

/*
 * TaskDone::task($task, ['member_creditcard_id' => $member_creditcard_id])->done();
 */