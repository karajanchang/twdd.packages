<?php


namespace Twdd\Jobs\Login;


use App\Jobs\Job;
use Illuminate\Support\Facades\Log;
use Twdd\Facades\PushNotification;
use Twdd\Models\LoginIdentify;

class PushNotify extends Job
{
    private $identity;
    private $title;
    private $body;

    public function __construct(LoginIdentify $identity, $title, $body)
    {
        $this->identity = $identity;
        $this->title = $title;
        $this->body = $body;
    }

    public function handle(){
        if(strlen($this->identity->type)==0){

            return ;
        }
        if($this->identity->type=='driver'){
            $pushNotification = PushNotification::driver();
        }else{
            $pushNotification = PushNotification::user();
        }

        $push = $this->identity->push;
        if(empty($push->DeviceType) || empty($push->PushToken)){
            Log::info('Login Push Notify error, 該駕駛沒有push token:', [$this->identity]);

            return false;
        }
        $deviceType = strtolower($push->DeviceType);
        if ($deviceType == 'android') {
            $pushNotification->android();
        }
        $res = $pushNotification->action('logout')->title($this->title)->body($this->body)->tokens([$push->PushToken])->send();

        //---通知成功
        if(isset($res['success']) && $res['success']=='ok' && !isset($res['logs'][0]['error'])){

            return true;
        }

        Log::info('Login Push Notify exception:', [$res]);
        return false;
    }
}