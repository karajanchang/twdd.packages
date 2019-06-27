<?php
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class MoneyAccount extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'MoneyAccount';
    }
}
