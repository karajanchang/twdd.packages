<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-21
 * Time: 09:34
 */

namespace Twdd\Listeners;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Log;
use Twdd\Events\NotificationDriverMatchCancel;
use Twdd\Facades\PushNotification;


class NotificationDriverMatchCancelListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ExampleEvent $event
     * @return void
     */
    public function handle(NotificationDriverMatchCancel $event)
    {
        $call = $event->call;
        if(isset($call->driver)) {
            try {
                $pushNotification = PushNotification::user();
                $push = $call->driver->driverpush;
                $deviceType = strtolower($push->DeviceType);
                if ($deviceType == 'android') {
                    $pushNotification->android();
                }
                $pushNotification->tokens([$push->PushToken])->title('此任務已取消')->action('user_have_cancel_match')->obj($call)->send();
            } catch (\Throwable $e) {
                Bugsnag::notifyException($e);
                Log::error('can not send notification: ' . $e->getMessage(), [$e]);
            }
        }
        Log::info('call cancel', [$call]);
    }
}