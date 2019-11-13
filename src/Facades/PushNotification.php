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

/*
 *
 * ##1. 送推播給會員
    PushNotification::user($device_type)->tokens($tokens)->action('PushMsg')->title($title)->body($body);

    $device_type 格式為數值 (1: 'ios', 2: 'andriod')
    $device_type 格式為字串 ('ios' or 'andriod')

##2. 送推播給司機
    PushNotification::driver($device_type)->tokens($tokens)->action('PushMsg')->title($title)->body($body);

    $device_type 格式為數值 (1: 'ios', 2: 'andriod')
    $device_type 格式為字串 ('ios' or 'andriod')

 */