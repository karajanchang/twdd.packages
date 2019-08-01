<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:13
 */
namespace Twdd\Services\PushNotification;




class Gorush4User extends PushNotificationService
{
    public function __construct()
    {
        $this->host = env('GORUSH_DRIVER_HOST', 'http://localhost');
        $this->port = env('GORUSH_DRIVER_PORT', 7789);
        $this->topic = env('IOS_USER_TOPIC', 'com.rich.app.DesignedDrivingClient');
        $this->alert = new \stdClass();
        $this->badge = 3;
    }

}
