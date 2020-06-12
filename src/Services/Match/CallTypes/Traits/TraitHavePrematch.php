<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Twdd\Repositories\CalldriverTaskMapRepository;

trait TraitHavePrematch
{
    /*
     * 擋下在1.5小時內有預約呼叫的人
     */
    protected function HavePrematch() : bool{
        $count = app(CalldriverTaskMapRepository::class)->numsOfPrematchByMemberIdAndHour($this->member->id, env('MATCH_CANNOT_ACCEPT_WHEN_IN_PREMATH_HOUR', 1.5));

        return $count > 0;
    }
}
