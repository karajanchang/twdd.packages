<?php

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class RedisPushService extends Facade
{
    protected static function getFacadeAccessor() { return 'RedisPushService'; }
}