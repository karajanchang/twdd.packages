<?php


namespace Twdd\Repositories;


use Carbon\Carbon;
use Twdd\Models\ReserveDemand;

class ReserveDemandRepository
{
    private $model;

    public function __construct(ReserveDemand $reserveDemand)
    {
        $this->model = $reserveDemand;
    }

    public function store($params)
    {
        return $this->model->newQuery()->create($params);
    }

    public function getReserves(int $memberId)
    {
        $startOfTodayDt = Carbon::now()->startOfDay();
        return $this->model->newQuery()
            ->with(['startAddress', 'endAddress', 'calldriverTaskMap'])
            ->where('reserve_demand.member_id', $memberId)
            ->where('reserve_demand.reserve_datetime', '>=', $startOfTodayDt)
            ->get();
    }
}
