<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Twdd\Facades\TaskService;

trait TraitServiceArea
{
    /*
     * 檢查有沒有在服務區域
     */
    protected function ServiceArea(array $params){

        return TaskService::ServiceArea()->check($params);
    }
}
