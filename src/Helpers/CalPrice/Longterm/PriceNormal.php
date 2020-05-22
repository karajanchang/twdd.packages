<?php

namespace Twdd\Helpers\CalPrice\Longterm;


class PriceNormal implements InterfacePriceLongterm
{
    public function fire($settingPrice, float $distance, int $duration) : array
    {
        $start_price = $settingPrice->start_price;

        $other_distance = $distance - $settingPrice->base_mile;
        $unit_times = ceil($other_distance / $settingPrice->unit_mile);
        //dump('ooooooooooooooooooo', $distance, $settingPrice->base_mile, $other_distance, $settingPrice->unit_mile, $unit_times);
        $mile_price = $unit_times * $settingPrice->unit_price;

        //--超時費
        $bb = 3 * 60 * 60;
        $over_price = 0;
        if($duration > $bb){
            $other_duration = $duration - $bb;

            $over_price = $settingPrice->over_price + ceil($other_duration / 60 / $settingPrice->over_unit_minute ) * $settingPrice->over_unit_price;
        }

        return [
            'mile_price' => $mile_price,
            'start_price' => $start_price,
            'over_price' => $over_price,
            'total_price' => round($start_price + $mile_price + $over_price, 0),
        ];
    }

}
