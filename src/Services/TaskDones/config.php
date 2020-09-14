<?php

use Twdd\Services\TaskDones\Cash as CashDone;
use Twdd\Services\TaskDones\CarFactory as CarFactoryDone;
use Twdd\Services\TaskDones\Spgateway as SpgatewayDone;
use Twdd\Services\TaskDones\Enterprise as EnterpriseDone;
use Twdd\Services\TaskDones\ApplePay as ApplePayDone;

return [
    1 => CashDone::class,
    2 => SpgatewayDone::class,
    3 => EnterpriseDone::class,
    4 => CarFactoryDone::class,
    5 => ApplePayDone::class,
];

