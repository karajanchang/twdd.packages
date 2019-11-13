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

/*
 * 和 Bank::account($DriverID); 一樣
 * MoneyAccount::init('123456789');
 */