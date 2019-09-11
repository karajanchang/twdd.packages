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

    public function __construct(LoginIdentify $identity, $title)
    {
        $this->identity = $identity;
        $this->title = $title;
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
        $deviceType = strtolower($push->DeviceType);
        if ($deviceType == 'android') {
            $pushNotification->android();
        }
        $res = $pushNotification->action('PushMsg')->title($this->title)->body($this->title)->tokens([$push->PushToken])->send();

        //---通知成功
        if(isset($res['success']) && $res['success']=='ok' && !isset($res['logs'][0]['error'])){

            return true;
        }

        Log::info('Login Push Notify exception:', $res);
        return false;
    }
}