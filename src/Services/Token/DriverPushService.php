<?php


namespace Twdd\Services\Token;


use Twdd\Facades\PushNotification;
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

    public function send(int $device_type, string $action, string $title, string $body, array $tokens){
        if(count($tokens)==0){

            return false;
        }

        return PushNotification::driver($device_type)
                    ->action($action)
                    ->title($title)
                    ->body($body)
                    ->tokens($tokens);

    }
}