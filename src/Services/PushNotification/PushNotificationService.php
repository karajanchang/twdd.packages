<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:09
 */
namespace Twdd\Services\PushNotification;

use Illuminate\Support\Facades\Log;
use Twdd\Traits\AttributesArrayTrait;
use Zhyu\Facades\ZhyuCurl;

class PushNotificationService extends \Twdd\Services\ServiceAbstract
{
    use AttributesArrayTrait;

    protected $host = null;
    protected $port = 0;
    protected $alert = null;
    protected $data = null;
    //protected $platform = 1;
//    protected $tokens = [];

    public function platform(string $type = 'ios'){
        if(strtolower($type)=='ios'){
            $this->platform = 1;
        }else{
            $this->platform = 2;
        }

        return $this;
    }

    public function ios(){

        return $this->platform('ios');
    }

    public function android(){

        return $this->platform('android');
    }

    public function tokens(array $tokens = []){
        $this->tokens = array_unique($tokens);

        return $this;
    }

    public function title(string $title){
        $this->alert->title = $title;
        $this->title = $title;
        return $this;
    }
    public function body(string $body){
        $this->alert->body = $body;
        $this->msg = $body;

        return $this;
    }

    public function data($data){
        $this->data = $data;

        return $this;
    }

    private function makeNotification(){
        $notification = $this->toArray();
        $notification['alert'] = $this->alert;
        $notification['data']['data'] = $this->data;

        Log::info('$notification==============$notification', $notification);
        //dump('$notification==============$notification', $notification);
        return $notification;
    }

    public function send(){
        $send = new \stdClass();
        $send->notifications[] = $this->makeNotification();


        $url = $this->host.':'.$this->port.'/api/push';
        //dump($url);
        //dd('send ... send... send ...', $send);

        //--正式機才發推播
        if(env('APP_ENV')=='production') {
            $res = ZhyuCurl::url($url)->json($send, true);
        }else{
            $res = [
                'counts' => 0,
                'success' => 'ok',
            ];
            if(count($this->tokens)==1){
                $res = ZhyuCurl::url($url)->json($send, true);
            }
        }

        $send = null;

        $this->tokens = [];

        return $res;
    }


}
