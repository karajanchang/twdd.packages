<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverInsuranceBackLog extends Model
{
    public $table = 'driver_insurance_back_logs';
    public $timestamps = true;

    public $guarded = ['id'];
}