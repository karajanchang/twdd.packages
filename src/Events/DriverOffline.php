<?php
namespace Twdd\Events;

use App\Events\Event;
use Twdd\Models\Driver;

class DriverOffline extends Event
{
    public $driver;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

}
