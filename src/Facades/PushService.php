<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:22
 */

namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class PushService extends Facade {
    protected static function getFacadeAccessor() { return 'PushService'; }
}
/*
 * 修改登入者的PushToken和DeviceType
 * PushService::createOrUpdateByLoginIdentity($loginIdentify);
 *
 * 以任務來發送推播
 * PushService::task($task)->action('action')->title('title')->body('body')->send2Driver();
 *
 * PushService::task($task)->action('action')->title('title')->body('body')->send2Member();
 */