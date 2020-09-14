<?php


namespace Twdd\Events;


use Twdd\Models\Task;

class ApplePayErrorEvent extends Event
{
    public $task;
    public $result = null;

    public function __construct(Task $task, $result = null)
    {
        $this->task = $task;
        $this->result = $result;
    }

}