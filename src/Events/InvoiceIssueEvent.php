<?php


namespace Twdd\Events;


class InvoiceIssueEvent extends Event
{
    public $params;

    /**
     * Params should include:
     *
     * type B2B or B2C
     * fee 費用
     * target model of the enterprise or member
     * belong one of those model: task, calldriver_task_map, enterprise_bill
     * 
     */

    public function __construct(Array $params)
    {
        $this->params = $params;
    }

}