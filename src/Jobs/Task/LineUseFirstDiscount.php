<?php


namespace Twdd\Jobs\Task;


use App\Jobs\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LineUseFirstDiscount extends Job
{
    public $task;

    public function __construct(Model $task)
    {
        $this->task = $task;
    }

    public function handle(){
        DB::table('line_maps')->where('member_id', '=', $this->task->member_id)->where('use_first_discount', 0)->update([
            'use_first_discount' => 1
        ]);
    }
}