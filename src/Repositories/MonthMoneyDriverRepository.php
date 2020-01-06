<?php


namespace Twdd\Repositories;


use Carbon\Carbon;
use Twdd\Models\MonthMoneyDriver;
use Zhyu\Repositories\Eloquents\Repository;

class MonthMoneyDriverRepository extends Repository
{
    public function model(){

        return MonthMoneyDriver::class;
    }

    //--['nums' => '總次數', 'sumTaskFee' => '總金額', 'money' => '總金額-twddFee總和']
    public function createOrUpdateByDriverId(int $driver_id, Carbon $dt, array $params){
        $params['driver_id'] = $driver_id;
        $params['tyear'] = $dt->year;
        $params['tmonth'] = $dt->month;
        $params['sumCouponTaskFee'] = isset($params['sumCouponTaskFee']) ? $params['sumCouponTaskFee'] : 0;
        $params['sumCouponnumsDriver'] = isset($params['sumCouponnumsDriver']) ? $params['sumCouponnumsDriver'] : 0;
        $params['sumCouponmoneyDriver'] = isset($params['sumCouponmoneyDriver']) ? $params['sumCouponmoneyDriver'] : 0;
        $params['sumGroupnums'] = isset($params['sumGroupnums']) ? $params['sumGroupnums'] : 0;
        $params['sumGroupmoney'] = isset($params['sumGroupmoney']) ? $params['sumGroupmoney'] : 0;
        $params['couponmoneyNums'] = isset($params['couponmoneyNums']) ? $params['couponmoneyNums'] : 0;


        return $this->updateOrCreate([
             'driver_id' => $driver_id,
             'tyear' => $dt->year,
             'tmonth' => $dt->month,
         ], $params);
    }
}