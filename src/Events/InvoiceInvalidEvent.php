<?php


namespace Twdd\Events;


class InvoiceInvalidEvent extends Event
{
    public $params;

    /**
     * Params should include:
     *
     * type B2B or B2C
     * model call_driver_task_map's model
     * 
     */
    public function __construct(Array $params)
    {
        $this->params = $params;
    }

}