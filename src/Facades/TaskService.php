<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:22
 */

namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class TaskService extends Facade {
    protected static function getFacadeAccessor() { return 'TaskService'; }
}

/*
 *
 *
 *##1. 檢查是否有在服務區域內
    $params = [
        'lat' => 25.0000,
        'lon' => 120.000,
        'zip' => 290,
    ];
    bool TaskService::ServiceArea()->check($params);

##2. 檢查該會員是否有重覆呼叫
    bool TaskService::calldriver($member)->checkIfDuplicate();

##3. 檢查該司機是否有在進行中的任務中
    bool TaskService::task()->checkNotHaveInProcessTaskStateByDriver($driver)

##4. 檢查該會員是否有在進行中的任務
    bool TaskService::task()->checkNotHaveInProcessTaskStateByMember($member)

##5. 得到該task的資料
    $task = TaskService::task()->profile($task->id, $columns = ['*'], $$clear_cache = true);

 *
 *
 *
 */
