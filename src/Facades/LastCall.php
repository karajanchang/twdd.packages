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