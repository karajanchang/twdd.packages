<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:35
 */
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class SmsMemberRegister extends Facade
{
	protected static function getFacadeAccessor() { return 'SmsMemberRegister'; }
}


/*
 *
 * ##1.會員註冊傳送認證簡訊
    $res = SmsMemberRegister::to($mobile)->code($code)->send();
 *
 */