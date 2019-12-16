<?php


namespace Twdd\Facades;


use Illuminate\Support\Facades\Facade;

class MqttPushService extends Facade
{
    protected static function getFacadeAccessor() { return 'MqttPushService'; }
}