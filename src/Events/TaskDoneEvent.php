<?php


namespace Twdd\Events;


use Twdd\Models\Task;

class TaskDoneEvent extends Event
{
    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

}