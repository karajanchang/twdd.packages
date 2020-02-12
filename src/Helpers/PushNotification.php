<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:46
 */

namespace Twdd\Helpers;


use Illuminate\Support\Facades\Log;
use Twdd\Services\PushNotification\Gorush4driver;
use Twdd\Services\PushNotification\Gorush4user;

class PushNotification
{
    private $service;
    private $tokens = [];
    private $action;
    private $obj;

    private $types = [ 1 => 'ios', 2 => 'android' ];

    private function initType($type){
        if(is_null($type)){
            return $this;
        }
        if(is_int($type)){
            if(!key_exists($type, $this->types)){
                throw new \Exception('value of type must 1 or 2');
            }
            $type = $this->types[$type];
        }
        $this->service->platform($type);
    }

    public function user($type = 'ios'){
        $this->service = app()->make(Gorush4user::class);
        $this->initType($type);

        return $this;
    }

    public function driver($type = 'ios'){
        $this->service = app()->make(Gorush4driver::class);
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

    private function makeData(array $params = []){
        $data = new \stdClass();
        $data->serial = uniqid();
        $data->code = 0;
        $data->action = $this->action;

        $service = $this->service->toArray();
        $data->title = isset($service['title']) ? $service['title'] : '';
        $data->msg = isset($service['msg']) ? $service['msg'] : '';
        $data->obj = $this->obj;

        Log::info('Helpers PushNotification service: ', [$service]);
        Log::info('Helpers PushNotification data: ', [$data]);

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
        $data = $this->makeData();
        //dump($data);
        //$this->service->tokens($this->tokens);
        $this->service->data($data);

        $this->tokens = [];
        return $this->service->send();
    }
}
