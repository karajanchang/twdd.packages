<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Twdd\Repositories\MemberTempCanNotCallRepository;

trait TraitMemberCanNotCall
{
    /*
     * 檢查該會員是否被暫時停止呼叫 (因為取消過多被罰) 惡意取消」防治惡意取消服務
     */
    protected function MemberCanNotCall() : int{
        $endTS = app(MemberTempCanNotCallRepository::class)->endTSFromCanNotCallByMemberId($this->member->id);

        return $endTS;
    }
}