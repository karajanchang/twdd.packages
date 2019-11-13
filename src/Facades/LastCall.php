<?php

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class LastCall extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'LastCall';
    }
}
/*
 *
  $call = LastCall::UserPhone($mobile)->cancel($user_cancel_reason_id);

    若有帶user，會加上檢查是否為此user的單（這功能大部份為網呼用）
    $call = LastCall::UserPhone($mobile)->user($user)->cancel($user_cancel_reason_id);
*/
