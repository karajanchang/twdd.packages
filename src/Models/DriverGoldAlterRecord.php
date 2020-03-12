<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverGoldAlterRecord extends Model
{
    protected $table = 'driver_gold_alter_record';
    public $timestamps = true;

    protected $guarded = ['id'];
}