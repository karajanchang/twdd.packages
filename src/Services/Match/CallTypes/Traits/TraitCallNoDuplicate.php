<?php


namespace Twdd\Services\Match\CallTypes\Traits;


trait TraitCallNoDuplicate
{
    /*
     * 檢查有沒有重覆呼叫
     */
    protected function CallNoDuplicate() : bool{
        $checkIfDublicate = $this->getCalldriverServiceInstance()->checkIfDuplicate();
        if($checkIfDublicate!==true) {

            return false;
        }

        return true;
    }
}
