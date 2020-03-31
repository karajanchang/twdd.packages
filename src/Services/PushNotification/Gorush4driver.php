<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:13
 */
namespace Twdd\Services\PushNotification;

class Gorush4driver extends PushNotificationService
{
    public function __construct()
    {
        $this->host = env('GORUSH_DRIVER_HOST', 'http://localhost');
        $this->port = env('GORUSH_DRIER_PORT', 7788);
        $this->topic = env('IOS_DRIVER_TOPIC', 'com.tw.twdd.driver');
        $this->alert = new \stdClass();
        $this->badge = 3;
    }

}
