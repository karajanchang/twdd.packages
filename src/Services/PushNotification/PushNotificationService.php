<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:09
 */
namespace Twdd\Services\PushNotification;

use Illuminate\Support\Collection;
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
    protected $sound = 'default';
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
        $this->message = $body;

        return $this;
    }

    public function sound($sound){
        if(is_int($sound)){
            $sounds = include_once __DIR__.'/sound.php';
            $this->sound = Collection::make($sounds)->get($sound, 0);

            return $this;
        }
        if(is_string($sound)){
            $this->sound = $sound;

            return $this;
        }
    }

    public function data($data){
        $this->data = $data;

        return $this;
    }

    private function makeNotification(){
        if($this->platform==1){

            return $this->iosNotificaiton();
        }
        //dump('$notification==============$notification', $notification);
        return $this->androidNotification();
    }
    private function iosNotificaiton(){
        $notification = $this->toArray();
        $notification['alert'] = $this->alert;
        $notification['data']['data'] = $this->data;
        $notification['data']['action'] = $this->action;
        $notification['sound'] = $this->sound;


        Log::info('$notification ios ==============$notification', $notification);

        return $notification;
    }
    private function androidNotification(){
        $notification = $this->toArray();
        $notification['alert'] = $this->alert;
        $notification['data']['data'] = $this->data;
        $notification['data']['action'] = $this->action;

        Log::info('$notification android ==============$notification', $notification);

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
