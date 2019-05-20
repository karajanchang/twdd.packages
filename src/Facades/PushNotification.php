<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:41
 */

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class PushNotification extends Facade
{
    protected static function getFacadeAccessor() { return 'PushNotification'; }
}
