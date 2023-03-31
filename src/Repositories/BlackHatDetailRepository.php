<?php

namespace Twdd\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Twdd\Models\BlackhatDetail;
use Zhyu\Repositories\Eloquents\Repository;

class BlackHatDetailRepository extends Repository
{
    public function model()
    {
        return BlackhatDetail::class;
    }

    public function getMemberReserves(int $memberId)
    {
        $startOfTodayDt = Carbon::now()->startOfDay();
        return $this->getReserves()
            ->select(DB::raw('"1" AS reserve_type'), 'blackhat_detail.*', 'blackhat_detail.type AS black_hat_type', 'calldriver.TS' ,'calldriver.type', 'calldriver.call_type', 'calldriver.pay_type', 'calldriver.addr', 'calldriver.addrKey')
            ->where('calldriver_task_map.member_id', $memberId)
            ->whereNotNull('calldriver_task_map.call_driver_id')
            ->where('blackhat_detail.start_date', '>=', $startOfTodayDt)
            ->get();
    }

    public function getMemberReserve(int $memberId, int $calldriverTaskMapId)
    {
        return $this->getReserves()
            ->select(DB::raw('"1" AS reserve_type'), 'blackhat_detail.*', 'blackhat_detail.type AS black_hat_type',
                'calldriver.TS' ,'calldriver.type', 'calldriver.call_type', 'calldriver.pay_type', 'calldriver.addr',
                'calldriver.addrKey', 'calldriver_task_map.call_driver_id')
            ->where('calldriver_task_map.member_id', $memberId)
            ->where('calldriver_task_map.id', $calldriverTaskMapId)
            ->first();
    }

    private function getReserves() : Builder
    {
        return $this->model
            ->join('calldriver_task_map', 'blackhat_detail.calldriver_task_map_id', '=', 'calldriver_task_map.id')
            ->join('calldriver', 'calldriver_task_map.calldriver_id', '=', 'calldriver.id');
    }
}
