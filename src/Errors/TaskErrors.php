<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;

use ArrayAccess;

class TaskErrors extends ErrorAbstract
{
    protected $unit = 'task';

    public function error1001(){
        return trans('errors.out_of_service_area');
    }

    public function error1002(){
        return trans('errors.this_location_can_not_parse');
    }

    public function error1003(){
        return trans('errors.this_district_is_not_open_yet');
    }

}
