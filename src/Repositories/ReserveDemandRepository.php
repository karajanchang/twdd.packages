<?php


namespace Twdd\Repositories;


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
}
