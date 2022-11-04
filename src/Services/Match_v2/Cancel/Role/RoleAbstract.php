<?php


namespace Twdd\Services\Match_v2\Cancel\Role;


abstract class RoleAbstract
{
    protected $cancelBy;

    public function getCancelBy()
    {
        return $this->cancelBy;
    }
}
