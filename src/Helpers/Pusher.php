<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:23
 */

namespace Twdd\Helpers;


use Twdd\Services\PusherService;
use Twdd\Services\Task\CalldriverService;

class Pusher
{
    /**
     * @var PusherService
     */
    private $pusherService;

    /**
     * @var CalldriverService
     */
    private $calldriverService;

    public function __construct(PusherService $pusherService, CalldriverService $calldriverService){
        $this->pusherService = $pusherService;
        $this->calldriverService = $calldriverService;
    }

    public function webcallNotify(int $calldriver_id){
        if(!isset($calldriver_id) && $calldriver_id<=0){

            return null;
        }
        $call = $this->calldriverService->currentCall($calldriver_id);

        return $this->pusherService->send(env('PUSHER_NOTIFY_WEBCALL_CHANNEL').$calldriver_id, env('PUSHER_NOTIFY_WEBCALL_EVENT'), $call);
    }
}
