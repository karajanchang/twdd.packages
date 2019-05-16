<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-16
 * Time: 14:16
 */

namespace Twdd\Services\Task;


use Carbon\Carbon;
use Twdd\Models\InterfaceModel;

class InitalObject
{
    public static function parseDriverFromCall(InterfaceModel $call = null){
        if(!isset($call->driver)){
            $d = new Driver();
            $d->DriverID = '';
            $d->DriverName = '';
            $d->DriverPhoto = '';
            $d->DriverRating = 0;
            $d->DriverDrivingYear = 0;
            $d->DriverLatlon = null;

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
    public static function parseTaskFromCall(InterfaceModel $call = null){
        if(!isset($call->task_id)) {

            return self::emptyTask();
        }
        $task = $call->task;
        if(!isset($task->id)){

            return self::emptyTask();
        }

        return $task;
    }

    public static function parseLatLonFromCall(InterfaceModel $call = null){
        if(isset($call->lat) && strlen($call->lat)>0 && isset($call->lat) && strlen($call->lat)>0){
            $latlon = $call->lat.','.$call->lon;

            return $latlon;
        }

        return null;
    }

}
