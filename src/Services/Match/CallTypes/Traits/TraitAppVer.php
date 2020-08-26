<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Zhyu\Facades\ZhyuTool;

trait TraitAppVer
{
    /*
     * 檢查App版本
     */
    protected function AppVer(array $attributes) : bool{
        $DeviceType = !empty($attributes['DeviceType']) ? strtolower(trim($attributes['DeviceType'])) : null;
        $AppVer = !empty($attributes['AppVer']) ? $attributes['AppVer'] : null;
        if(!is_null($DeviceType) && !is_null($AppVer)){
            if($DeviceType=='iphone'){
                $mini_ver = env('APP_VER_VER_IOS', '3.6.5');
            }else{
                $mini_ver = env('APP_MINI_VER_ANDROID', '3.4.2');
            }
            if($AppVer < $mini_ver){

                return false;
            }

            //--因為true代表過期，所以這邊要反過來
            return !ZhyuTool::versionOutOfDate($AppVer, $mini_ver);
        }

        return true;
    }
}
