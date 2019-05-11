<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:23
 */

namespace Twdd\Helpers;


class TaskService
{
    public function ServiceArea(){
        $app = app()->make(\Twdd\Services\Task\ServerviceArea::class);

        return $app;
    }
}
