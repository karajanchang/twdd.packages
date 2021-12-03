<?php


namespace Twdd\Services\Match\CallTypes\Traits;


use Zhyu\Facades\ZhyuTool;

trait TraitAppVer
{
    /*
     * 檢查App版本
     */
    protected function AppVer(array $attributes): bool
    {
        $deviceType = isset($attributes['DeviceType']) ? strtolower(trim($attributes['DeviceType'])) : null;
        $appVer = $attributes['AppVer'] ?? null;
        $osVer = $attributes['OSVer'] ?? null;

        if (empty($deviceType) || empty($appVer) || empty($osVer)) {
            return true;
        }

        $OsMiniForceIOS = env('OS_MINI_FORCE_IOS', '13');
        switch ($deviceType) {
            case 'iphone':
                // iOS 13 以下不進行強更
                if (version_compare($osVer, $OsMiniForceIOS, '<')) {
                    return true;
                }
                $miniVer = env('APP_MINI_VER_IOS', '3.6.5');
                break;
            case 'android':
                $miniVer = env('APP_MINI_VER_ANDROID', '3.4.2');
                break;
        }

        if (!isset($miniVer)) {
            return true;
        }

        return version_compare($appVer, $miniVer, '>=');
    }
}
