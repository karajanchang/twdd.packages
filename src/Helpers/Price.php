<?php

namespace Twdd\Helpers;

use Twdd\Helpers\CalPrice\CalPriceService;

class Price
{

    public function trial(string $startAddr, string $endAddr, int $TS)
    {
        $res = app(CalPriceService::class, ['startAddr' => $startAddr, 'endAddr' => $endAddr, 'TS' => $TS])->trial();

        return $res;
    }


}
