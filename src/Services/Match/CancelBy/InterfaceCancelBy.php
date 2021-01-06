<?php


namespace Twdd\Services\Match\CancelBy;


interface InterfaceCancelBy
{
    public function cancelCalldriverTaskMap(array $params = null);

    public function cancelTask(array $params = null);

    public function processParams(array $params);

    //---檢查看是否可以取消，返回true即可
    public function check();
}