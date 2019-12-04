<?php


namespace Twdd\Repositories;


use Twdd\Models\DriverDayNums;
use Zhyu\Repositories\Eloquents\Repository;

class DriverDayNumsRepository extends Repository
{
    public function model(){

        return DriverDayNums::class;
    }

    public function insertByDriverId(int $driver_id, int $nums, int $money){
        if($driver_id==0){

            return false;
        }

        $nums = $this->countByDriverIdAndCdate($driver_id);
        if($nums>0){

            return $this->updateByDriverIdAndCdate($driver_id, $nums, $money);
        }else{

            return $this->insertByDriverIdAndCdate($driver_id, $nums, $money);
        }
    }

    public function countByDriverIdAndCdate(int $driver_id){

        return $this->where('driver_id', $driver_id)->where('cdate', date('Y-m-d'));
    }

    public function insertByDriverIdAndCdate(int $driver_id, int $nums, int $money){

        return
        $this->create([
            'driver_id' => $driver_id,
            'nums' => $nums,
            'money' => $money,
            'cdate' => date('Y-m-d'),
        ]);
    }

    public function updateByDriverIdAndCdate(int $driver_id, int $nums, int $money){

        return
        $this->where('driver_id', $driver_id)->where('cdate', date('Y-m-d'))->create([
            'driver_id' => $driver_id,
            'nums' => $nums,
            'money' => $money,
            'cdate' => date('Y-m-d'),
        ]);
    }
}