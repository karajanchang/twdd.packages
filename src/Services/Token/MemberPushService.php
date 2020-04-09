<?php


namespace Twdd\Services\Token;


use Twdd\Facades\PushNotification;
use Twdd\Models\LoginIdentify;
use Twdd\Repositories\MemberPushRepository;

class MemberPushService
{
    public function __construct(MemberPushRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createOrUpdateByLoginIdentity(LoginIdentify $loginIdentify){
        $res = $this->repository->updateOrCreate([
                'member_id' => $loginIdentify->id
            ],
            [
                'PushEnv' => $loginIdentify->PushEnv,
                'PushToken' => $loginIdentify->PushToken,
                'DeviceType' => $loginIdentify->DeviceType,
            ]
        );

        return $res;
    }

    public function send(int $device_type, string $action, string $title, string $body, array $tokens, $obj = null){
        if(count($tokens)==0){

            return false;
        }

        $push = PushNotification::user($device_type)
            ->action($action)
            ->title($title)
            ->body($body)
            ->obj($obj)
            ->tokens($tokens)
            ->send();

    }
}