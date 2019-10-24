<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverPush extends Model
{
    protected $table = 'driver_push';
    public $timestamps = false;

    protected $guarded = ['id'];

}
