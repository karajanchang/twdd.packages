<?php


namespace Twdd\Services\Token;


use Twdd\Models\LoginIdentify;
use Twdd\Repositories\DriverPushRepository;

class DriverPushService
{
    private $repository;

    public function __construct(DriverPushRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createOrUpdateByLoginIdentity(LoginIdentify $loginIdentify){
        $res = $this->repository->updateOrCreate([
                'driver_id' => $loginIdentify->id
            ],
            [
                'PushToken' => $loginIdentify->PushToken,
                'DeviceType' => $loginIdentify->DeviceType,
            ]
        );

        return $res;
    }
}