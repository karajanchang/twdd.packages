<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverTmpOffline extends Model implements InterfaceModel
{
    protected $table = 'driver_tmp_offline';
    public $timestamps = true;

    protected $guarded = ['id'];

    public function driver(){

        return $this->belongsTo(Driver::class);
    }

}
