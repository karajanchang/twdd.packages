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

    private function iosData(array $params = []){
        if(count($params)){

            return $params;
        }
        $data = new \stdClass();
        $data->serial = uniqid();
        $data->code = 0;
        $data->action = $this->action;

        $service = $this->service->toArray();
        $data->title = isset($service['title']) ? $service['title'] : '';
        $data->message = isset($service['msg']) ? $service['msg'] : '';
        $data->obj = $this->obj;

        Log::info('Helpers PushNotification service: ', [$service]);
        Log::info('Helpers PushNotification data: ', [$data]);

        return $data;
    }

    private function androidData(array $params = []){
        $service = $this->service->toArray();
        $data = new \stdClass();
        $data->serial = uniqid();
        $data->code = 0;
        $data->action = $this->action;
        $data->title = isset($service['title']) ? $service['title'] : '';
        $data->msg = isset($service['msg']) ? $service['msg'] : '';
        if(count($params)){
            $data->data = $params;
            Log::info('Helpers PushNotification data: ', [$data]);

            return $data;
        }

        $data->obj = $this->obj;
        Log::info('Helpers PushNotification obj: ', [$data]);

        return $data;
    }

    private function makeData(array $params = []){
        if($this->service->platform==1){

            return $this->iosData($params);
        }

        return $this->androidData($params);
    }

    public function action($action){
        $this->action = $action;

        return $this;
    }

    public function obj($obj){
        $this->obj = $obj;

        return $this;
    }

    public function send(array $params = [], $sound = null, bool $is_send_tester = false){
        $data = $this->makeData($params);

        if(!is_null($sound)){
            $this->service->sound($sound);
        }
        $this->service->data($data);

        $this->tokens = [];

        $res = $this->service->send();

        //--送給測試者
        if($is_send_tester===true){
            $this->sendTesters($params, $sound, 2);
            $this->sendTesters($params, $sound, 1);
        }

        return $res;
    }

    private function getTesters($type) : array{
        if($type==2) {
            $testers = $this->service->iosTesters();
        }else{
            $testers = $this->service->androidTesters();
        }

        return $testers;
    }

    private function sendTesters(array $params, $sound = null, int $type = 1) : void{
        $testers = $this->getTesters($type);

        $data = $this->makeData($params);

        dump('sendTesters', ['sound' => $sound, 'params' => $params, 'type' => $type]);
        if(!is_null($sound)){
            $this->service->sound($sound);
        }
        $this->service->data($data);
        if(isset($testers) && count($testers)>0){
            $this->service->tokens($testers);

            $this->service->send();
        }
    }
}
