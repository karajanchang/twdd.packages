<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:23
 */

namespace Twdd\Helpers;


use Twdd\Services\PusherService;

class Pusher
{
    /**
     * @var PusherService
     */
    private $pusherService;

    public function __construct(PusherService $pusherService){
        $this->pusherService = $pusherService;
    }

    public function webcallNotify(int $calldriver_id, array $data = []){
        if(!isset($calldriver_id) && $calldriver_id<=0){

            return null;
        }
        $channel = env('PUSHER_NOTIFY_WEBCALL_CHANNEL').$calldriver_id;

        return $this->pusherService->send($channel, env('PUSHER_NOTIFY_WEBCALL_EVENT'), $data);
    }
}
