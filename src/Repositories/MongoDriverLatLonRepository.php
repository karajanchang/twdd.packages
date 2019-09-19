<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Twdd\Models\MongoDriverLatLon;
use Zhyu\Repositories\Eloquents\Repository;

class MongoDriverLatLonRepository extends Repository
{
    private $driver;
    private $params;
    private $msg;

    public function model(){

        return MongoDriverLatLon::class;
    }

    //---下線
    public function offline(Model $driver, array $params){
        $params['type'] = 2;
        //--以司機下線的狀況為driver_state
        $params['driver_state'] = 0;
        $this->driver = $driver;
        $this->params = $params;
        $this->msg = '下線成功';

        return $this->insertByDriverId();
    }

    //---上線
    public function online(Model $driver, array $params){
        $params['type'] = 1;
        //--以司機上線的狀況為driver_state
        $params['driver_state'] = 1;
        $this->driver = $driver;
        $this->params = $params;
        $this->msg = '上線成功';

        return $this->insertByDriverId();
    }

    //---更新位置
    public function updateLocation(Model $driver, array $params){
        $params['type'] = 1;
        //--以司機目前的狀況為driver_state
        $params['driver_state'] = $driver->DriverState;
        $this->driver = $driver;
        $this->params = $params;
        $this->msg = '更新位置';

        return $this->insertByDriverId();
    }

    private function insertByDriverId(){
        $now = Carbon::now();
        $all = [
            'type' => $this->params['type'],
            'ts' => $now->timestamp,
            'time' => $now->format('Hi'),
            'year' => $now->format('Y'),
            'month' => $now->format('n'),
            'day' => $now->format('j'),
            'date' => $now->format('date'),
            'created_at' => $now->toDateTimeString(),
            'city_id' => $this->params['city_id'],
            'district_id' => $this->params['district_id'],
            'latlon' => $this->params['lat'].','.$this->params['lon'],
            'device_token' => $this->params['device_token'],
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->DriverName,
            'driver_state' => $this->params['driver_state'],
            'msg' => $this->msg,
        ];
        return $this->create($all);
    }
}
