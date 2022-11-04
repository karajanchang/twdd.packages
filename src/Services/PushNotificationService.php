<?php


namespace Twdd\Services;

use Twdd\Facades\PushNotification;
use Twdd\Models\DriverPush;
use Twdd\Models\MemberPush;

class PushNotificationService
{
    public function push(array $memberIds, string $title, string $body, string $action = 'PushMsg')
    {
        $tokens = $this->getMemberPushTokens($memberIds);
        $this->send2User($tokens, $title, $body, $action);
    }

    public function push2Driver(array $driverIds, string $title, string $body, string $action = 'PushMsg')
    {
        $tokens = $this->getDriverPushTokens($driverIds);
        $this->send2Driver($tokens, $title, $body, $action);
    }

    private function send2User(array $tokens, string $title, string $body, string $action = 'PushMsg')
    {
        if (count($tokens['ios']) > 0) {
            $tokens['ios'] = collect($tokens['ios']);
            $tokens['ios'] = $tokens['ios']->chunk(50)->toArray();
            foreach ($tokens['ios'] as $token) {
                PushNotification::user('ios')->tokens($token)->action($action)->title($title)->body($body)->send();
            }
        }
        if (count($tokens['android']) > 0) {
            $tokens['android'] = collect($tokens['android']);
            $tokens['android'] = $tokens['android']->chunk(50)->toArray();
            foreach ($tokens['android'] as $token) {
                PushNotification::user('android')->tokens($token)->action($action)->title($title)->body($body)->send();
            }
        }
    }

    private function send2Driver(array $tokens, string $title, string $body, string $action = 'PushMsg')
    {
        if (count($tokens['ios']) > 0) {
            $tokens['ios'] = collect($tokens['ios']);
            $tokens['ios'] = $tokens['ios']->chunk(50)->toArray();
            foreach ($tokens['ios'] as $token) {
                PushNotification::driver('ios')->tokens($token)->action($action)->title($title)->body($body)->send();
            }
        }
        if (count($tokens['android']) > 0) {
            $tokens['android'] = collect($tokens['android']);
            $tokens['android'] = $tokens['android']->chunk(50)->toArray();
            foreach ($tokens['android'] as $token) {
                PushNotification::driver('android')->tokens($token)->action($action)->title($title)->body($body)->send();
            }
        }
    }

    private function getMemberPushTokens(array $memberIds) : array
    {
        $tokens = [
            'ios' => [],
            'android' => [],
        ];
        if (count($memberIds) == 0) return $tokens;

        $memberPush = MemberPush::select('DeviceType', 'PushToken')
            ->whereIn('member_id', $memberIds)
            ->get();

        $tokens['ios'] = $memberPush->where('DeviceType', 'iPhone')->pluck('PushToken')->toArray();
        $tokens['android'] = $memberPush->where('DeviceType', 'Android')->pluck('PushToken')->toArray();

        return $tokens;
    }

    private function getDriverPushTokens(array $driverIds) : array
    {
        $tokens = [
            'ios' => [],
            'android' => [],
        ];
        if (count($driverIds) == 0) return $tokens;

        $memberPush = DriverPush::select('DeviceType', 'PushToken')
            ->whereIn('driver_id', $driverIds)
            ->get();

        $tokens['ios'] = $memberPush->where('DeviceType', 'iPhone')->pluck('PushToken')->toArray();
        $tokens['android'] = $memberPush->where('DeviceType', 'Android')->pluck('PushToken')->toArray();

        return $tokens;
    }
}
