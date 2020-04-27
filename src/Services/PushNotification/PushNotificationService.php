<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-20
 * Time: 16:09
 */
namespace Twdd\Services\PushNotification;

use Illuminate\Database\Eloquent\Collection;
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
    protected $is_send_test = false;

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

    public function tokens($tokens){
        if(!is_array($tokens)){
            $this->tokens = [$tokens];
        }else {
            $this->tokens = array_unique($tokens);
        }

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

    public function sound($sound){
        if(is_int($sound)){
            $sounds = include __DIR__ . '/sound.php';
            $this->sound = Collection::make($sounds)->get($sound, 0);
            Log::info(__CLASS__.' sound (int): '.$sound, [$this->sound]);

            return $this;
        }
        if(is_string($sound)){
            $this->sound = $sound;
            Log::info(__CLASS__.' sound (string): '.$sound, [$this->sound]);

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

        return $this->androidNotification();
    }

    private function iosNotificaiton(){
        $notification = $this->toArray();
        $notification['alert'] = $this->alert;
        $notification['data']['data'] = $this->data;
        $notification['data']['action'] = $this->action;
        $this->sound($this->sound);
        $notification['sound'] = $this->sound;

        Log::info('$notification ios ==============$notification', $notification);

        return $notification;
    }

    private function androidNotification(){
        $notification = $this->toArray();
        $notification['data']['data'] = $this->data;
        UNSET($notification['title']);
        UNSET($notification['msg']);
        UNSET($notification['action']);
        UNSET($notification['port_dev']);
        UNSET($notification['topic']);
        UNSET($notification['badge']);
        UNSET($notification['port_dev']);

        Log::info('$notification android ==============$notification', $notification);

        return $notification;
    }

    private function getSend() : \stdClass{
        $send = new \stdClass();
        $send->notifications[] = $this->makeNotification();

        return $send;
    }


    private function testers(string $type='ios') : array{
        $this->is_send_test = true;
        $this->platform($type);

        $name = strtolower($type);
        $rows = app($this->testRepository())->with($name.'Push')->where('receive_push_notification', 1)->get();
        $testers = new Collection();
        if( isset($rows) && count($rows)>0 ) {
            foreach ($rows as $row) {
                if (isset($row->{$name.'Push'}->PushToken)) {
                    $testers->push($row->{$name.'Push'}->PushToken);
                }
            }
        }

        return $testers->toArray();
    }

    public function iosTesters() : array{

        return $this->testers('ios');
    }

    public function androidTesters() : array{

        return $this->testers('android');
    }

    public function send(){
        $send = $this->getSend();

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
            //dump($this->is_send_test);
            if(count($this->tokens)==1 || $this->is_send_test===true){
                $this->iosPortDynamicChangeByToken($this->tokens[0]);
                $res = ZhyuCurl::url($url)->json($send, true);
            }
        }

        $send = null;
        $this->tokens = [];

        return $res;
    }
}
