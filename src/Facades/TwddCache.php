<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:35
 */
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class TwddCache extends Facade
{
    protected static function getFacadeAccessor() { return 'TwddCache'; }
}