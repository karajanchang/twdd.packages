<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-15
 * Time: 16:54
 */

namespace Twdd\Services;

use Pusher\Pusher;

class PusherService
{
    private $pusher;

    public function __construct()
    {
        $options = array(
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true
        );
        $this->pusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);

        return $this;
    }

    public function send($channel, $event, array $msg){

        return $this->pusher->trigger($channel, $event, $msg);
    }

}
