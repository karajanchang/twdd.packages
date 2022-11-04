<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Illuminate\Support\Facades\Log;
use Twdd\Models\Driver;
use Zhyu\Repositories\Eloquents\Repository;

class DriverRepository extends Repository
{

    public function model()
    {
        return Driver::class;
    }

    private function stateText(int $DriverState){

        switch($DriverState){
            case 1:
                $state = '上線';
                break;
            case 2:
                $state = '任務中';
                break;
            default:
                $state = '下線';
        }

        return $state;
    }

    public function updateDriverState(int $id, int $DriverState){

        $res = $this->update($id, [
            'DriverState' => $DriverState,
        ]);


        $text = $res==1 ? '成功' : '失敗';

        Log::info('駕駛'.$this->stateText($DriverState).'：'.$text, [$res]);

        return $res;
    }

    public function updateDriverPassword(int $id, string $DriverPassword){

        return $this->update($id, [
            'DriverPassword' => $DriverPassword,
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
        $driver = $this->find($id, ['id', 'driver_gold_nums', 'is_used_gold']);
        if($driver->driver_gold_nums==0){
            Log::info('叩掉駕駛金牌==>不叩因為已為0張 '.$driver->id.': ', ['driver_golden_nums' => $driver->driver_gold_nums]);

            return false;
        }
        Log::info('叩掉駕駛金牌 '.$driver->id.': ', ['driver_golden_nums' => $driver->driver_gold_nums]);
        $driver->driver_gold_nums = $driver->driver_gold_nums - 1;

        #達成任務後關閉金牌
        if ($autoClose === true) {
            $driver->is_used_gold = 0;
        }
        $driver->save();


        return $driver;
    }

    //--從DriverID得到model
    public function findGoldenById(int $id){

        return $this->find($id, ['id', 'driver_gold_nums', 'is_used_gold']);
    }

    //--從DriverID得到model
    public function findByDriverID(string $DriverID, array $columns = ['*']){
        $qb = $this->where('DriverID', $DriverID);

        return $qb->select($columns)->first();
    }
}
