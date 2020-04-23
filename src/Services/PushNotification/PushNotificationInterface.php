<?php


namespace Twdd\Services\PushNotification;


use Illuminate\Database\Eloquent\Collection;

Interface PushNotificationInterface
{
    public function iosPortDynamicChangeByToken(string $token);
    public function testRepository() : string;
}