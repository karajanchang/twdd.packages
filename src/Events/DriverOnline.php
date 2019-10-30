<?php
namespace Twdd\Events;

use Twdd\Models\Driver;

class DriverOnline extends Event
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
