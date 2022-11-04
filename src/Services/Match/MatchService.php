<?php


namespace Twdd\Services\Match;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Twdd\Services\Match\CallTypes\InterfaceMatchCallType;
use Twdd\Services\ServiceAbstract;

class MatchService extends ServiceAbstract
{

    private $callType;

    public function __construct(InterfaceMatchCallType $callType)
    {
        $this->callType = $callType;
    }

    /**
     * @param mixed $member
     */
    public function member(Model $member): MatchService
    {
        $this->callType->setMember($member);

        return $this;
    }

    /**
     * @param mixed $user
     */
    public function user(Model $user): MatchService
    {
        $this->callType->setUser($user);

        return $this;
    }

    /**
     * @param mixed $callMember
     */
    public function callMember(Model $callMember): MatchService
    {
        $this->callType->setCallMember($callMember);

        return $this;
    }

    /**
     * @param mixed $callDriver
     */
    public function callDriver(Model $callDriver): MatchService
    {
        $this->callType->setCallDriver($callDriver);

        return $this;
    }

    public function check(array $params, array $check_lists = ['*']){

        return $this->callType->check($params, $check_lists);
    }

    public function cancel_check(array $params, array $check_lists = ['*']){

        return $this->callType->cancel_check($params, $check_lists);
    }

    public function match(array $other_params = []){

        return $this->callType->match($other_params);
    }

    public function cancel(int $calldriverTaskMapId, array $other_params = []){

        return $this->callType->cancel($calldriverTaskMapId, $other_params);
    }

    public function matchPay(int $calldriverTaskMapId)
    {
        return $this->callType->matchPay($calldriverTaskMapId);
    }

    public function matchDriver(array $other_params = [], array $refuseDriverId = [0]) {
        return $this->callType->matchDriver($other_params, $refuseDriverId);
    }

}
