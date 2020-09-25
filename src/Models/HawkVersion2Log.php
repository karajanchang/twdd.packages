<?php


namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class HawkVersion2Log extends Model
{
    protected $table = 'hawk_version2_logs';
    public $timestamps = true;

    protected $guarded = ['id'];

}
