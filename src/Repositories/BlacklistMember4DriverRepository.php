<?php


namespace Twdd\Repositories;



use Twdd\Models\BlacklistMember4Driver;
use Zhyu\Repositories\Eloquents\Repository;

class BlacklistMember4DriverRepository extends Repository
{
    public function model()
    {
        return BlacklistMember4Driver::class;
    }

    public function add($member_id, $driver_id){

        return $this->firstOrCreate(['member_id' => $member_id, 'driver_id' => $driver_id]);
    }

    /*
     * 該會員是否為永久黑名單
     */
    public function isAlwaysBlackListByMemberId(int $member_id) : bool{
        $nums = $this->where('member_id', '=', $member_id)->where('driver_id', '=', 0)->count();

        return $nums != 0;
    }
}