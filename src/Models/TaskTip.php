<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;


class TaskTip extends Model {

    protected $table = 'task_tip';

    protected $guarded = ['id'];

    public $timestamps = true;
}
