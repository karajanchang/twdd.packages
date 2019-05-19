<?php
namespace Twdd\Events;

use App\Events\Event;
use Twdd\Models\CalldriverTaskMap;

class CancelCall extends Event
{
    public $call;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(CalldriverTaskMap $call)
    {
        $this->call = $call;
    }
}
