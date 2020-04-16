<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:13
 */
namespace Twdd\Services\PushNotification;


use Twdd\Repositories\MemberTesterRepository;

class Gorush4user extends PushNotificationService implements PushNotificationInterface
{
    public function __construct()
    {
        $this->host = env('GORUSH_USER_HOST', 'http://localhost');
        $this->port = env('GORUSH_USER_PORT', 7789);
        $this->port_dev = env('GORUSH_USER_PORT_DEV', 7791);
        $this->topic = env('IOS_USER_TOPIC', 'com.rich.app.DesignedDrivingClient');
        $this->alert = new \stdClass();
        $this->badge = 3;
    }

    public function testRepository() : string{

        return MemberTesterRepository::class;
    }
}
