<?php


namespace Twdd\Helpers;


use Twdd\Services\ServiceAbstract;

class Bank extends ServiceAbstract
{
    public function account(string $DriverID){
        $DriverID = trim($DriverID);

        return $DriverID.$this->vcode($DriverID);
    }

    public function vcode($DriverID){
        $code9 = trim($DriverID);

        $_array = array(4, 5, 6, 7, 8, 9, 1, 2, 3, 4, 5, 6, 7);
        $code13 = '5038'.trim($code9);
        $size = strlen($code13);
        if($size!=13) return ;

        $parray = array();
        for($i=0; $i<$size; $i++){
            $parray[$i] = ($code13[$i]*$_array[$i])%10;
        }

        $sum = array_sum($parray);
        $p = $sum%10;
        $t = 10-$p;

        return substr($t, (strlen($t)-1), 1);
    }
}