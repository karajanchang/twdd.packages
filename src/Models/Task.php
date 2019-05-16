<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class Task extends Model implements InterfaceModel
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

}
