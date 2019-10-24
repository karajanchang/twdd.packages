<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-16
 * Time: 14:16
 */

namespace Twdd\Services\Task;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InitalObject
{
    public static function parseDriverFromCall(Model $call = null){
        if(!isset($call->driver)){
            $d = new Driver();
            $d->DriverID = '';
            $d->DriverName = '';
            $d->DriverPhone = '';
            $d->DriverPhoto = '';
            $d->DriverRating = 0;
            $d->DriverDrivingYear = 0;
            $d->DriverLatlon = null;
            $d->DriverServiceTime = 0;

            return $d;
        }
        $driver = $call->driver;

        $dt = Carbon::now();
        $driver->DriverDrivingYear = $dt->diffInYears(Carbon::instance(new \DateTime($driver->DriverDrivingSeniorityDate)));
        $latlon = $driver->location;
        if(isset($latlon->id) && $latlon->id>0) {
            $driver->DriverLatlon = $latlon->DriverLat . ',' . $latlon->DriverLon;
        }

        return $driver;
    }

    public static function emptyTask(){
        $t = new \stdClass();
        $t->id = 0;
        $t->TaskState = null;

        return $t;
    }
    public static function parseTaskFromCall(Model $call = null){
        if(!isset($call->task_id)) {

            return self::emptyTask();
        }
        $task = $call->task;
        if(!isset($task->id)){

            return self::emptyTask();
        }

        return $task;
    }

    public static function parseLatLonFromCall(Model $call = null){
        if(isset($call->lat) && strlen($call->lat)>0 && isset($call->lat) && strlen($call->lat)>0){
            $latlon = $call->lat.','.$call->lon;

            return $latlon;
        }

        return null;
    }

}
