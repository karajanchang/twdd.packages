<?php
/*
 * 更改司機或個人的PushToken DeviceType
 */

namespace Twdd\Helpers;


use Exception;
use Illuminate\Support\Collection;
use Twdd\Models\LoginIdentify;
use Twdd\Services\Token\DriverPushService;
use Twdd\Services\Token\MemberPushService;

class PushService
{
    private $lut = [
                    'driver' => DriverPushService::class ,
                    'member' => MemberPushService::class ,
                ];
    private $collection;

    public function __construct()
    {
        $this->collection = new Collection($this->lut);

        return $this;
    }

    public function app($type=null){
        if(is_null($type)){
           throw new Exception('Must provide type value!');
        }
        $class = $this->collection->get($type, '');
        $app = app()->make($class);

        return $app;
    }


    public function createOrUpdateByLoginIdentity(LoginIdentify $loginIdentify){
        $app = $this->app($loginIdentify['type']);
        
        return $app->createOrUpdateByLoginIdentity($loginIdentify);
    }

}