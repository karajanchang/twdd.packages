<?php

namespace Twdd\Helpers;

use App\User;
use Twdd\Repositories\MemberRepository;
use Twdd\Services\Task\LastCall as LastCallService;

class LastCall {
    private $service;
    private $memberRepository;

    public function __construct(LastCallService $service, MemberRepository $memberRepository)
    {
       $this->service = $service;
       $this->memberRepository = $memberRepository;

       return $this;
    }

    public function UserPhone($UserPhone){
        $member = $this->memberRepository->findBy('UserPhone', $UserPhone);
        $this->service->setMember($member);

        return $this;
    }

    public function memberId($member_id){
        $member = $this->memberRepository->find($member_id);
        $this->service->setMember($member);

        return $this;
    }

    public function user(User $user){
        $this->service->setUser($user);

        return $this;
    }

    public function info(){

        return $this->service->info();
    }
}