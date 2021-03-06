<?php


namespace Twdd\Repositories;


use Illuminate\Support\Facades\Log;
use Twdd\Models\MemberPayToken;
use Zhyu\Repositories\Eloquents\Repository;

class MemberPayTokenRepository extends Repository
{
    public function model()
    {
        return MemberPayToken::class;
    }

    public function createByMemberId(int $member_id, string $token, int $pay_type = 3){
        $params = [
            'member_id' => $member_id,
            'token' => $token,
            'pay_type' => $pay_type,
        ];

        return $this->create($params);
    }

    public function lastByMemberIdAndPayType(int $member_id, int $pay_type){
        $res = $this->where('member_id', $member_id)->where('pay_type', $pay_type)->latest('id')->first();
        Log::info(__CLASS__.'::'.__METHOD__.' : ', ['member_id' => $member_id, 'pay_type' => $pay_type, 'res' => $res]);

        return $res;
    }

}