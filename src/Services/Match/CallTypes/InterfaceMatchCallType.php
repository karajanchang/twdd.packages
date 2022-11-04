<?php

namespace Twdd\Services\Match\CallTypes;


Interface InterfaceMatchCallType
{

    public function rules() : array;

    public function checkOtherInviteCodeIsValid(string $OtherInviteCode = null) : bool;

    public function check(array $params, array $remove_lists = []);

    public function processParams(array $params, array $other_params = []) : array;

    public function match(array $other_params = []);

    public function cancel(int $calldriverTaskMapId, array $other_params = []);

}
