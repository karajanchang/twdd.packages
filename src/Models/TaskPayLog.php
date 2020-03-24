<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:09
 */

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;

class TaskPayLog extends Model
{
    protected $table = 'task_pay_logs';
    public $timestamps = false;

    protected $guarded = ['id'];

    public function task(){
        return $this->belongsTo(Task::class);
    }

    public function member(){
        return $this->belongsTo(Member::class);
    }

    public function memberCreditcard(){

        return $this->belongsTo(MemberCreditcard::class);
    }
}
