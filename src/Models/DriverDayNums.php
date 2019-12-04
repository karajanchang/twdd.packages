<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class DriverDayNums extends Model
{
    protected $table = 'driver_day_nums';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'driver_id', 'nums', 'money', 'cdate'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
}