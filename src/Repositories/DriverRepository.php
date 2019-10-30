<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Twdd\Models\Driver;
use Zhyu\Repositories\Eloquents\Repository;

class DriverRepository extends Repository
{

    public function model()
    {
        return Driver::class;
    }

    public function updateDriverState(int $id, int $DriverState){

        return $this->update($id, [
            'DriverState' => $DriverState,
        ]);
    }

    public function updateDriverPassword(int $id, string $DriverPassword){

        return $this->update($id, [
            'DriverState' => $DriverPassword,
        ]);
    }

    public function profile(int $id, array $columns = []){
        $rcolumns = [
            'driver_group_id', 'DriverName', 'DriverNameEn', 'DriverPhoto', 'idno', 'DriverEmail', 'DriverID', 'DriverPhone', 'DriverAddress', 'DriverZip', 'DriverGender', 'DriverEmergencyName', 'DriverEmergencyPhone', 'DriverEmergencyGender', 'DriverEmergencyRelation',
            'offline_reason', 'is_online', 'is_out', 'is_used_gold', 'driver_gold_nums', 'DriverCredit', 'DriverState', 'DriverRating', 'DriverScore', 'DriverServiceTime', 'DriverNew', 'DriverDrivingSeniorityDate', 'isNotifyNoCredit', 'isFake', 'ajStar',
            'is_accept_daytime_service', 'is_accept_longterm_service', 'is_accept_prematch_service', 'is_accept_creditcard', 'pass_rookie_times', 'is_pass_rookie'
        ];
        if(count($columns)){
            $rcolumns = $columns;
        }

        return $this->find($id, $rcolumns);
    }

    public function modDriverCredit(int $id, int $DriverCredit){

        return $this->update($id, [
            'DriverCredit' => $DriverCredit,
        ]);
    }

    //---叩掉金牌
    public function reduceGoldenNums(int $id, bool $autoClose = false){
        $driver = $this->find($id);
        $driver->driver_gold_nums = $driver->driver_gold_nums - 1;

        #達成任務後關閉金牌
        if ($autoClose === true) {
            $driver->is_used_gold = 0;
        }
        $driver->save();

        return $driver;
    }
}