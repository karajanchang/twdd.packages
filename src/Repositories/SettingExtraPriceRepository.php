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

    public function allOpen(int $city_id, string $code = null){

        $TS = Carbon::now()->timestamp;

        $qb = $this->leftJoin('setting_extra_price_city', 'setting_extra_price.id', '=', 'setting_extra_price_city.setting_extra_price_id')
                    ->where('startTS', '<=', $TS)->where('endTS', '>=', $TS);

        if($city_id > 0){
           $qb->where(function($query) use($city_id){
               $query->whereNull('setting_extra_price_city_id')
                     ->orWhere('city_id', $city_id);
           });
        }

        if(!is_null($code) && strlen($code) > 0 ){
           $qb->where('code', $code);
        }


        return $qb->get();
    }

}