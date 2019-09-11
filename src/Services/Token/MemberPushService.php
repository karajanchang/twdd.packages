<?php


namespace Twdd\Services\Token;


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
                'PushToken' => $loginIdentify->PushToken,
                'DeviceType' => $loginIdentify->DeviceType,
            ]
        );

        return $res;
    }
}