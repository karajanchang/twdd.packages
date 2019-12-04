<?php


namespace Twdd\Events;


use Illuminate\Support\Facades\Log;
use Twdd\Models\Task;

class TaskDoneEvent extends Event
{
    public $task;

    public function __construct(Task $task)
    {
        Log::info('TaskDoneEvent', [$task]);
        $this->task = $task;
    }

}