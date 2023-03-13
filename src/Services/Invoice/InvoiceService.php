<?php 

namespace Twdd\Services\Invoice;
use Twdd\Services\Invoice\InvoiceInterface;
use Illuminate\Database\Eloquent\Model;


class InvoiceService 
{
    public $type;
    public function __construct(InvoiceInterface $type)
    {
        $this->type = $type;
    }

    /**
     * @param mixed $member
     */
    public function member(Model $member): InvoiceService
    {
        $this->type->setMember($member);

        return $this;
    }

    /**
     * @param mixed $enterprise
     */
    public function enterprise(Model $enterprise): InvoiceService
    {
        $this->type->setEnterprise($enterprise);

        return $this;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params): InvoiceService
    {
        $this->type->setParams($params);

        return $this;
    }

    public function issue() :array
    {
        return $this->type->issue();
    }

    public function invalid() :array
    {
        return $this->type->invalid();
    }

    public function calldriverTaskMap(Model $calldriverTaskMap): InvoiceService
    {
        $this->type->setCalldriverTaskMap($calldriverTaskMap);

        return $this;
    }

    public function task(Model $task): InvoiceService
    {
        $this->type->setTask($task);
        $task->load('calldriver_task_map');
        $this->type->setCalldriverTaskMap($task->calldriver_task_map);

        return $this;
    }

}