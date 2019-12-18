<?php


namespace Twdd\Services\PushNotification;


use Illuminate\Database\Eloquent\Model;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;
use Mqtt;

class MqttPushService extends ServiceAbstract implements \ArrayAccess
{
    use AttributesArrayTrait;

    private $client;
    private $topic;
    private $action;
    private $title;
    private $body;
    private $data = [];

    public function client(Model $client){
        $this->client = $client;

        return $this;
    }

    public function topic(string $topic){
        $this->topic = $topic;

        return $this;
    }

    public function action(string $action){
        $this->action = $action;

        return $this;
    }

    public function title(string $title){
        $this->title = $title;

        return $this;
    }

    public function body(string $body){
        $this->body = $body;

        return $this;
    }

    public function data(array $data = []){
        $this->data = $data;

        return $this;
    }


    private function getContent(){

        $data = [
            'action' => $this->action,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function push(string $topic = null, string $action = null, string $title = null, string $body = null, array $data = []){
        if(!is_null($topic)){

            $this->topic($topic);
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
        if(!is_null($data)){

            $this->data($data);
        }


        $output = Mqtt::ConnectAndPublish($this->topic, $this->getContent(), $this->client->id);

        if ($output === true)
        {
            return true;
        }

        return false;
    }

}