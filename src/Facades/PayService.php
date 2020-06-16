<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:35
 */
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class PayService extends Facade
{
    protected static function getFacadeAccessor() { return 'PayService'; }
}

/*
 * ##1.讓該Task付款

    $params = [
            'payer_email' => 'test@test.com',
                （Spgateway用，可不帶會用DriverEmail）
            'is_random_serial' => true,
                （Spgateway用，預設為false，true時會重新去產生刷卡的OrderNo）
        ];
    PayService::by($pay_type)->task($task)->pay($params);

##2.取消該Task付款 (還沒寫)

    PayService::by($pay_type)->task($task)->cancel();

##3.返還該Task付款 (還沒寫)

    PayService::by($pay_type)->task($task)->back();

##4.查詢該Task付款 (還沒寫)

    PayService::by($pay_type)->task($task)->query();


##5.Task付款，用別種金額
    PayService::by($pay_type)->task($task)->money(100)->query();
 */