<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 15:57
 */

namespace Twdd\Services\Coupon;


use Illuminate\Support\Facades\DB;

class CouponCode
{
    public function init(){
        $code = $this->genCode();

        return $code;
    }

    private function genCode(){
        $array1 = [ "1", "2", "3", "4", "5", "6", "7", "8", "9" ];
        $array2 = [ "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z" ];

        $array = array_merge($array1, $array2);
        $len = count($array)-1;

        $code = '';
        for($i=0; $i<9; $i++){
            $key=rand(0, $len);
            $code.=$array[$key];
        }
        $qb = DB::table('coupon')->where('code', '=', $code);

        $count = $qb->count();
        if($count>0){

            return $this->genCode();
        }

        return $code;
    }
}
