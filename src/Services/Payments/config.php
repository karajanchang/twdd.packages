<?php

use Twdd\Services\Payments\Cash;
use Twdd\Services\Payments\CarFactory;
use Twdd\Services\Payments\Spgateway;
use Twdd\Services\Payments\Enterprise;
use Twdd\Services\Payments\ApplePay;

return [
    1 => Cash::class, //現金
    2 => Spgateway::class, //智付通
    3 => Enterprise::class, //企業簽單
    4 => CarFactory::class, //車廠
    5 => ApplePay::class, //ApplePay
];

