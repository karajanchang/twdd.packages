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
