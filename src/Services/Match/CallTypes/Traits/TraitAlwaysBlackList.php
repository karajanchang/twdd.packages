<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Twdd\Repositories\BlacklistMember4DriverRepository;

trait TraitAlwaysBlackList
{
    /*
     * 檢查是否在永久黑名單裡
     */
    protected function isAlwaysBlackList() : bool{

        return app(BlacklistMember4DriverRepository::class)->isAlwaysBlackListByMemberId($this->member->id);
    }
}
