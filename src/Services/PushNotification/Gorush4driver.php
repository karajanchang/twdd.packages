<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:13
 */
namespace Twdd\Services\PushNotification;

use Twdd\Repositories\DriverPushRepository;
use Twdd\Repositories\DriverTesterRepository;

class Gorush4driver extends PushNotificationService implements PushNotificationInterface
{
    public function __construct()
    {
        $this->host = env('GORUSH_DRIVER_HOST', 'http://localhost');
        $this->port = env('GORUSH_DRIER_PORT', 7788);
        $this->port_dev = env('GORUSH_DRIER_PORT_DEV', 7790);
        $this->topic = env('IOS_DRIVER_TOPIC', 'com.tw.twdd.driver');
        $this->alert = new \stdClass();
        $this->badge = 3;
    }

    public function iosPortDynamicChangeByToken(string $token){
        $PushEnv = app(DriverPushRepository::class)->checkIfIsByPushEnv($token);
        if($PushEnv!==false){
            if($PushEnv!='production'){
                $this->port = $this->port_dev;
            }
        }
    }

    public function testRepository() : string{

        return DriverTesterRepository::class;
    }


}
