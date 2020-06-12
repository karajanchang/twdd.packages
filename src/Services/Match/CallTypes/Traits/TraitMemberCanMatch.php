<?php

namespace Twdd\Services\Match\CallTypes\Traits;


trait TraitMemberCanMatch
{
    /*
     * 檢查該會員是否可以使用此呼叫，在各別Call裡可以去覆寫來針對有特殊需求但允許
     */
    protected function MemberCanMatch() : bool{
        if($this->member->is_online!=1){

            return false;
        }

        return true;
    }
}
