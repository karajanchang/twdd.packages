<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;

class MemberErrors extends ErrorAbstract
{
    protected $unit = 'member';

    public function error1001(){

        return trans('twdd::errors.no_user_id');
    }

    public function error1002(){

        return trans('twdd::errors.no_user_password');
    }

    public function error1003(){

        return trans('twdd::errors.no_user_push_token');
    }

    public function error1004(){

        return trans('twdd::errors.no_user_phone');
    }

    public function error2003(){

        return trans('twdd::errors.this_member_doesnot_exist');
    }

}