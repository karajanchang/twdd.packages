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
use Illuminate\Support\Facades\Log;
use Twdd\Models\MongoDriverLatLon;

class MongoDriverLatLonRepository
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
        //--以駕駛下線的狀況為driver_state
        $params['driver_state'] = 0;
        $this->driver = $driver;
        $this->params = $params;
        $this->msg = '下線成功';

        return $this->insertByDriverId();
    }

    //---上線
    public function online(Model $driver, array $params){
        $params['type'] = 1;
        //--以駕駛上線的狀況為driver_state
        $params['driver_state'] = 1;
        $this->driver = $driver;
        $this->params = $params;
        $this->msg = '上線成功';

        return $this->insertByDriverId();
    }

    //---更新位置
    public function updateLocation(Model $driver, array $params){
        $params['type'] = 3;
        //--以駕駛目前的狀況為driver_state
        $params['driver_state'] = $driver->DriverState;
        $this->driver = $driver;
        $this->params = $params;
        $this->msg = '更新位置';

        return $this->insertByDriverId();
    }

    private function insertByDriverId(){
        $dt = isset($this->params['ts']) ? Carbon::createFromTimestamp($this->params['ts']) : Carbon::now();
        $all = [
            'type' => $this->params['type'],
            'ts' => $dt->timestamp,
            'time' => $dt->format('Hi'),
            'year' => $dt->format('Y'),
            'month' => $dt->format('n'),
            'day' => $dt->format('j'),
            'date' => $dt->format('date'),
            'created_at' => $dt->toDateTimeString(),
            'city_id' => $this->params['city_id'],
            'district_id' => $this->params['district_id'],
            'latlon' => $this->params['lat'].','.$this->params['lon'],
            'device_token' => $this->params['device_token'],
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->DriverName,
            'driver_state' => $this->params['driver_state'],
            'msg' => $this->msg,
        ];
        Log::info('MongoDriverLatLonRepository params:', $all);

        return app($this->model())->create($all);
    }
}
