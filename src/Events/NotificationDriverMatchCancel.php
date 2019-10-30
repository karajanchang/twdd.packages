<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-21
 * Time: 09:33
 */

namespace Twdd\Events;

use Twdd\Models\CalldriverTaskMap;

class NotificationDriverMatchCancel extends Event
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
