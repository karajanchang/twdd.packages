<?php


namespace Twdd\Repositories;


use Twdd\Models\SettingLongtermPrice;
use Zhyu\Repositories\Eloquents\Repository;

class SettingLongtermPriceRepository extends Repository
{
    public function model()
    {
        return SettingLongtermPrice::class;
    }

    public function allByCityId(int $city_id = 1){

        return $this->all();
    }
}