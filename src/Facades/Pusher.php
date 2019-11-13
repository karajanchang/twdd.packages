<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:35
 */
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class Pusher extends Facade
{
    protected static function getFacadeAccessor() { return 'Pusher'; }
}

/*
 *
 * ##1.web call 透過pusher.com通知
    Pusher::webcallNotify($calldriver_id);
 */