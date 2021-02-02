<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Carbon\Carbon;
use Twdd\Models\SettingExtraPrice;
use Zhyu\Repositories\Eloquents\Repository;

class SettingExtraPriceRepository extends Repository
{

    public function model()
    {
        return SettingExtraPrice::class;
    }

    public function allOpen(){



        $res = $this->select(['setting_extra_price.id', 'setting_extra_price.code' , 'setting_extra_price.name', 'setting_extra_price.msg', 'setting_extra_price.startTS', 'setting_extra_price.endTS', 'setting_extra_price.price', 'city_id'])
                    ->leftJoin('setting_extra_price_city', 'setting_extra_price.id', '=', 'setting_extra_price_city.setting_extra_price_id')

                    ->where('is_open', 1)
                    ->get();

        return $res;
    }

}