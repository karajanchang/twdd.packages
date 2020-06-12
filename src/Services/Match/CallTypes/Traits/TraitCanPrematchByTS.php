<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Carbon\Carbon;

trait TraitCanPrematchByTS
{
    /*
     * 判斷此時段可不可以使用預約
     */
    protected function CanPrematchByTS(array $params) : bool{
        $dt = Carbon::createFromTimestamp($params['TS']);

        //---最少和最大的預約時間
        $can_prematch_long_between = EnvParseTSBetween('CAN_PREMATCH_LONG_BETWEEN', '3-48');
        if( $params['TS'] < $can_prematch_long_between[0] || $params['TS'] > $can_prematch_long_between[1] ){

            return false;
        }
        
        //--該時段不允許預約
        $can_prematch_hour_between = EnvParseBetween('CAN_PREMATCH_HOUR_BETWEEN', '8-19');
        $hour = $dt->format('G');
        if($hour < $can_prematch_hour_between[0] || $hour > $can_prematch_hour_between[1]){

            return false;
        }

        return true;
    }
}
