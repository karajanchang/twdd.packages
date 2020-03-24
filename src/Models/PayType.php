<?php

namespace Twdd\Models;

use Illuminate\Support\Collection;

class PayType {
    public function all(){

        return new Collection([
            0   =>  '無',
            1   =>  '現金',
            2   =>  '信用卡',
            3   =>  '企業簽單',
            4   =>  '車廠',
        ]);
    }
    public function get($key){

        return $this->all()->get($key, 0);
    }
}