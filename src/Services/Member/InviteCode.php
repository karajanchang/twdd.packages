<?php


namespace Twdd\Services\Member;


use Twdd\Models\Member;

class InviteCode
{
    public function init(){
        $length = 6;
        $string = '';
        for($i=1; $i<=$length;$i++){
            if($i%2==1){
                $string .= $this->RandomGoodEnglish();
            }else{
                $string .= rand(1, 9);
            }
        }
        $count = Member::where('InviteCode', $string)->count();
        if($count==1){

            return $this->init();
        }

        return $string;
    }

    public function RandomGoodEnglish(){
        $array = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'W', 'X', 'Y', 'Z'
        );

        $rkey = rand(0, (count($array)-1));
        $a = strtoupper($array[$rkey]);

        return $a;
    }
}