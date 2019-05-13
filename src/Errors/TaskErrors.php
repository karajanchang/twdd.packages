<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;


class TaskErrors extends ErrorAbstract
{
    protected $unit = 'task';

    public function error1001(){
        return trans('twdd::errors.out_of_service_area');
    }

    public function error1002(){
        return trans('twdd::errors.this_location_can_not_parse');
    }

    public function error1003(){
        return trans('twdd::errors.this_district_is_not_open_yet');
    }


    public function error1004(){
        return trans('twdd::errors.please_provider_valid_member_id');
    }

    public function error1005(){
        $replace = $this->getReplace();
        return trans('twdd::errors.duplicate_call_please_call_again_later', [ 'second' => $replace ]);
    }
}
