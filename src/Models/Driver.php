<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class Driver extends Model implements InterfaceModel
{
    protected $table = 'driver';
    public $timestamps = false;

    protected $guarded = ['id'];

    public function location(){
        
        return $this->hasOne(DriverLocation::class);
    }

    public function driverpush(){

        return $this->hasOne(DriverPush::class);
    }

}
