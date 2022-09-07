<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'task';
    public $timestamps = false;

    protected $guarded = ['id'];


    public function driver(){
        return $this->belongsTo(Driver::class);
    }

    public function task(){
        return $this->belongsTo(Task::class);
    }

    public function member(){
        return $this->belongsTo(Member::class);
    }

    public function calldriver_task_map(){
        return $this->hasOne(CalldriverTaskMap::class);
    }

    public function paylogs(){

        return $this->hasMany(TaskPayLog::class);
    }
}
