<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Twdd\Repositories\MemberCreditcardRepository;

trait TraitCheckHaveBindCreditCard
{
    private function CheckHaveBindCreditCard(){

        return app(MemberCreditcardRepository::class)->numsByMemberId($this->member->id) > 0 ? true : false;
    }
}