<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;

use ArrayAccess;

class CallErrors extends ErrorAbstract
{
    protected $unit = 'call';

    public function error1001(){

        return trans('twdd::errors.this_call_have_match_fail');
    }

    public function error1002(){

        return trans('twdd::errors.this_call_have_been_cancel_donot_duplicate_cancel');
    }



    public function error2003(){

        return trans('twdd::errors.this_call_doesnot_exist');
    }

}