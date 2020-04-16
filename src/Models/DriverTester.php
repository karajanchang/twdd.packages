<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverTester extends Model
{
    protected $table = 'driver_tester';
    public $timestamps = true;

    protected $guarded = ['id'];

    public function driver(){

        return $this->belongsTo(Driver::class);
    }

    public function androidPush(){

        return $this->belongsTo(DriverPush::class, 'driver_id', 'driver_id')->where('DeviceType', 'Android');
    }

    public function iosPush(){

        return $this->belongsTo(DriverPush::class, 'driver_id', 'driver_id')->where('DeviceType', 'iPhone');
    }
}
