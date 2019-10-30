<?php


namespace Twdd\Repositories;



use Twdd\Models\DriverInsuranceBack;
use Zhyu\Repositories\Eloquents\Repository;

class DriverInsuranceBackRepository extends Repository
{
    public function model(){

        return DriverInsuranceBack::class;
    }

    public function fetchLastByDriver(int $driver_id){

        return $this->where('driver_id', $driver_id)->whereColumn('money', '!=', 'money_last')->orderby('id', 'asc')->first();
    }
}