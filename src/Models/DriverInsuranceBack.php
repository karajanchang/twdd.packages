<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverInsuranceBack extends Model
{
    public $table = 'driver_insurance_backs';
    public $timestamps = true;

    public $guarded = ['id'];
}
