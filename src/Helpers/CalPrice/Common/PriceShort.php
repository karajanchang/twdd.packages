<?php

namespace Twdd\Helpers\CalPrice\Common;

class PriceShort implements InterfacePriceCommon
{
    public function fire($settingPrice, float $distance) : array{
        $short_price = $settingPrice->short_price;

        return [
            'mile_price' => 0,
            'start_price' => $short_price,
            'total_price' => $short_price,
        ];
    }
}
