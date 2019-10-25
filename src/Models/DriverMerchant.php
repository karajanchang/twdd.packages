<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverMerchant extends Model
{
    protected $table = 'driver_merchant';
    protected $primaryKey = 'driver_id';
    public $timestamps = true;

    protected $guarded = ['id'];

}
