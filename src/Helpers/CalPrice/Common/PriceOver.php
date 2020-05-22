<?php

namespace Twdd\Helpers\CalPrice\Common;

class PriceOver implements InterfacePriceCommon
{
    public function fire($settingPrice, float $distance) : array{
        $start_price = $settingPrice->start_price;

        $other_distance = 20;
        $unit_times = ceil($other_distance / $settingPrice->unit_mile);
        $mile_price20 = $unit_times * $settingPrice->unit_price;

        $other_distance = $distance - 30;
        $unit_times = ceil($other_distance / $settingPrice->over_unit_mile);
        $mile_price30 = $unit_times * $settingPrice->over_unit_price;

        $mile_price = $mile_price20 + $mile_price30;
        $total_price = $start_price + $mile_price;

        return [
            'mile_price' => $mile_price,
            'start_price' => $start_price,
            'total_price' => $total_price,
        ];
    }
}
