<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Twdd\Models\SettingServicePrice;
use Zhyu\Repositories\Eloquents\Repository;

class SettingServicePriceRepository extends Repository
{

    public function model()
    {
        return SettingServicePrice::class;
    }

    public function allByCityId($city_id){

        return $this->where('city_id', $city_id)->get();
    }

}