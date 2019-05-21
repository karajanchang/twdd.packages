<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:46
 */

namespace Twdd\Helpers;


use Twdd\Services\PushNotification\Gorush4user2driver;

class PushNotification
{
    private $service;
    private $tokens = [];
    private $action;
    private $obj;

    private function initType($type){
        if(is_null($type)){
            return $this;
        }
        $this->service->platform($type);
    }

    public function user(string $type = 'ios'){
        $this->service = app()->make(Gorush4user2driver::class);
        $this->initType($type);

        return $this;
    }

    public function driver(string $type = 'ios'){
        $this->service = app()->make(Gorush4driver2user::class);
        $this->initType($type);

        return $this;
    }

    public function __set($col, $val){
        $this->service->$col = $val;

        return $this;
    }
    public function __get($col){
        return $this->service->$col;
    }

    public function __call($name, $arguments)
    {
        call_user_func_array([$this->service, $name], $arguments);

        return $this;
    }

    public function pushToken($token){
        array_push($this->tokens, $token);
    }

    private function makeData(array $params = []){
        $data = new \stdClass();
        $data->serial = uniqid();
        $data->code = 0;
        $data->action = $this->action;

        $service = $this->service->toArray();
        $data->title = isset($service['title']) ? $service['title'] : '';
        $data->msg = isset($service['body']) ? $service['body'] : '';
        $data->obj = $this->obj;

        return $data;
    }

    public function action($action){
        $this->action = $action;

        return $this;
    }

    public function obj($obj){
        $this->obj = $obj;

        return $this;
    }

    public function send(){
        $this->service->data = $this->makeData();

        return $this->service->send();
    }
}
