<?php

namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class TokenService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'TokenService';
    }
}

/*
 *
 * ##1. 登入
    TokenService::driver()->login();
        post值送 DriverID DriverPassword

    TokenService::member()->login();
        post值送 UserPhone UserPassword

##2. 得到登入的id值
    $id = TokenService::id();
 *
 */