<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:23
 */

namespace Twdd\Helpers;


class MemberService
{
    public function register(array $params){
        $register = app()->make(\Twdd\Services\Member\Register::class);

        return $register->init($params);
    }
}
