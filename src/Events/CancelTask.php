<?php
namespace Twdd\Events;

use Twdd\Models\Task;

class CancelTask extends Event
{
    public $task;
    public $user_cancel_reason_id = 0;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Task $task, int $user_cancel_reason_id = 0)
    {
        $this->task = $task;
        $this->user_cancel_reason_id = $user_cancel_reason_id;
    }

}
