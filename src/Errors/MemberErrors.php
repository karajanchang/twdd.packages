<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-08
 * Time: 10:59
 */

namespace Twdd\Errors;

use ArrayAccess;

class MemberErrors extends ErrorAbstract
{
    protected $unit = 'member';

    public function error2001(){
        return 'msg';
    }

    public function error2002(){
        return trans('errors.no_user_phone');
    }

}