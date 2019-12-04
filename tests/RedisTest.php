<?php
//declare(strict_types=1);

namespace Twdd\Tests\Packages;

use PHPUnit\Framework\TestCase;
use Twdd\Services\PushNotification\RedisPushService;

class RedisTest extends TestCase
{
    public function testSend2Member(){
        $app = new RedisPushService();

        $app['channel'] = 'UserTask35';
        $app['action'] = 'driver_have_receive_match';
        $app['title'] = 'you have new ';
        $app['body'] = 'you have new ';

        $res = $app->push();

        dump($res);
        //$a = true;
        $this->assertEquals('UserTask35' , $app['UserTask35']);
    }

}