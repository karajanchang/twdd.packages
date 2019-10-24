<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class CalldriverTaskMap extends Model
{
    protected $table = 'calldriver_task_map';
    public $timestamps = false;

    protected $guarded = ['id'];


    public function calldriver(){
        return $this->belongsTo(Calldriver::class);
    }

    public function driver(){
        return $this->belongsTo(Driver::class);
    }

    public function task(){
        return $this->belongsTo(Task::class);
    }


}
