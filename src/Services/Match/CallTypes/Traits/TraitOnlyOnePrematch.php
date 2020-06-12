<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Twdd\Repositories\CalldriverTaskMapRepository;

trait TraitOnlyOnePrematch
{
    //---預約代駕在時間內一次只允許一筆
    protected function OnlyOnePrematch() : bool{

        return (app(CalldriverTaskMapRepository::class)->numsOfPrematchByMemberId($this->member->id) > 0)
                ? false
                : true;
    }
}
