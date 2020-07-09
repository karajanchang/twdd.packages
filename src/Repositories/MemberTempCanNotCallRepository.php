<?php


namespace Twdd\Repositories;


use Twdd\Models\MemberTempCanNotCall;
use Zhyu\Repositories\Eloquents\Repository;

class MemberTempCanNotCallRepository extends Repository
{
    public function model()
    {
        return MemberTempCanNotCall::class;
    }

    /*
     * 是否被暫時停權而不能呼叫
     */
    public function endTSFromCanNotCallByMemberId(int $member_id) : int{
        $now = time();

        $row = $this->where('member_id', $member_id)
                ->where('startTS', '<=', $now)
                ->where('endTS', '>=', $now)
                ->select('endTS')->first();

        return isset($row->endTS) && !empty($row->endTS) ? $row->endTS : 0;
    }
}