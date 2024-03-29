<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-02
 * Time: 16:20
 */

namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class CancelService extends Facade
{
    protected static function getFacadeAccessor() { return 'CancelService'; }
}

/*
 * 檢查看是否可以取消
 * CancelService::by($member|$driver|$user|$car_factory)->calldriverTaskMap($map)->task($task)->check();
 *
 * 被誰取消 1客人 member 2駕駛 driver 3客服 user 4車廠 car_factory
 * CancelService::by($member|$driver|$user|$car_factory)->calldriverTaskMap($map)->task($task)->cancel([
 *      'cancel_reason_id' => 1,
 *      'cancel_reason' => '今天我不爽所以要取消',
 * ]);
 */

/*
 * 檢查看是否要收取消違約費
 * CancelService::by($member|$driver|$user|$car_factory)->calldriverTaskMap($map)->task($task)->getCancelFees();
 *
 */

/*
 * 取消不收違約費
CancelService::by($member|$driver|$user|$car_factory)->calldriverTaskMap($map)->task($task)->cancelWithoutChargeFee([
       'cancel_reason_id' => 1,
       'cancel_reason' => '今天我不爽所以要取消',
 );
*/
