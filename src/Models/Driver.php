<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table = 'driver';
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['DriverPassword'];

    public function location(){

        return $this->hasOne(DriverLocation::class);
    }

    public function driverpush(){

        return $this->hasOne(DriverPush::class);
    }

    public function tmpOfflines(){

        return $this->hasMany(DriverTmpOffline::class);
    }

    //---is_tmp_offline
    public function getIsTmpOfflineAttribute($value){

        $now = Carbon::now();
        $count = $this->tmpOfflines->where('startTS', '<=', $now->timestamp)->where('endTS', '>=', $now->timestamp)->count();

        return $count>0 ? true : false;
    }

    public function isARookie(){
        $GG = intval(date('G'));
        if( $GG >= env('OLDBIRD_HOUR_START', 1) &&  $GG <= env('OLDBIRD_HOUR_END', 6) && $this->is_pass_rookie==false){

            return true;

        }

        return false;
    }

    public function driverGroup(){

        return $this->belongsTo(DriverGroup::class, 'driver_group_id');
    }

}
