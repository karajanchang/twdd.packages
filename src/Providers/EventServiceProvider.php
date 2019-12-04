<?php


namespace Twdd\Providers;

use Illuminate\Events\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        'Twdd\Events\TaskDoneEvent' => [
//            'Twdd\Listeners\Send15MinutesArriveCouponListener', //--15分未到送優惠卷
//            'Twdd\Listeners\LineUseFirstDiscountListener', //--line的首用
//            'Twdd\Listeners\DriverGoldenReduceListener', //--叩掉使用金牌
//            'Twdd\Listeners\TaskDoneGiveShareCouponListener', //--分享送優惠
            'Twdd\Listeners\CouponSetUsedListener', //--coupon設為已使用
            'Twdd\Listeners\DriverDayNumsListener', //--司機每日的任務數
        ],
        //--刷卡失敗
        'Twdd\Events\SpgatewayFailEvent' => [
            'Twdd\Listeners\SpgatewayErrorMailListener',
            'Twdd\Listeners\SpgatewayFailSmsListener',
        ],
        //--刷卡異常
        'Twdd\Events\SpgatewayErrorEvent' => [
            'Twdd\Listeners\SpgatewayFailMailListener',
            'Twdd\Listeners\SpgatewayErrorSmsListener',
        ],
    ];
}