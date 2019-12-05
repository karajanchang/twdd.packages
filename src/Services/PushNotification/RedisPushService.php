<?php


namespace Twdd\Services\PushNotification;


use Illuminate\Support\Facades\Redis;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class RedisPushService extends ServiceAbstract implements \ArrayAccess
{
    use AttributesArrayTrait;

    private $channel;
    private $action;
    private $title;
    private $body;
    private $obj;

    public function channel(string $channel){
        $this->channel = $channel;

        return $this;
    }

    public function action(string $action){
        $this->action = $action;

        return $action;
    }

    public function title(string $title){
        $this->title = $title;

        return $this;
    }

    public function body(string $body){
        $this->body = $body;

        return $this;
    }

    public function obj($obj){
        $this->obj = $obj;

        return $this;
    }

    private function getContent(){

        return [
            'action' => $this->action,
            'title' => $this->title,
            'body' => $this->body,
            'obj' => $this->obj,
        ];
    }
    public function push(string $channel = null, string $action = null, string $title = null, string $body = null, $obj = null){
        if(!is_null($channel)){

            $this->channel($channel);
        }
        if(!is_null($action)){

            $this->action($action);
        }
        if(!is_null($title)){

            $this->title($title);
        }
        if(!is_null($body)){

            $this->body($body);
        }
        if(!is_null($obj)){

            $this->obj($obj);
        }

        return Redis::publish($this->channel, $this->getContent());
    }

}