<?php

namespace Twdd\Helpers\CalPrice\Common;

class PriceNormal implements InterfacePriceCommon
{
    public function fire($settingPrice, float $distance) : array{
        $start_price = $settingPrice->start_price;
        if($distance>10) {
            $other_distance = $distance - $settingPrice->base_mile;
            $unit_times = ceil($other_distance / $settingPrice->unit_mile);
            $mile_price = $unit_times * $settingPrice->unit_price;
            $total_price = $start_price + $mile_price;
        }else{
            $mile_price = 0 ;
            $total_price = $start_price;
        }

        return [
            'mile_price' => $mile_price,
            'start_price' => $start_price,
            'total_price' => $total_price,
        ];
    }
}
