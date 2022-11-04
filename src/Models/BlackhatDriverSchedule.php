<?php
namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;

class BlackhatDriverSchedule extends Model
{
    public $timestamps = false;

    protected $table = 'blackhat_driver_schedule';

    protected $guarded = ['id'];

}
