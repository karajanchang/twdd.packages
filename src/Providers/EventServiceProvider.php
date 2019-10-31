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

            //---發出推播

        ],
    ];
}