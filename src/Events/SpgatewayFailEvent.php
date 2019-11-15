<?php

namespace Twdd\Events;

use Twdd\Models\Task;

class SpgatewayFailEvent extends Event
{

    public $task;
    public $result = null;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Task $task, $result = null)
    {
        $this->task = $task;

        $this->result = $result;
    }
}