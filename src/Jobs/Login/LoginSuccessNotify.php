<?php
namespace Twdd\Jobs\Login;

use App\Jobs\Job;
use Twdd\Facades\PushService;
use Twdd\Models\LoginIdentify;
use Illuminate\Support\Facades\Mail;

class LoginSuccessNotify extends Job
{
    private $identity;

    public function __construct(LoginIdentify $identity)
    {
        $this->identity = $identity;
    }

    public function handle(){
        Mail::to($this->identity->email)->queue(new \Twdd\Mail\Login\SuccessMail($this->identity));

        //---通知上一個登入的裝置，你的帳號被從另一裝置登入
        dispatch(new PushNotify($this->identity, '你的帳號被從另一裝置登入，若非你本人的操作請通知公司'));

        //建立或更新PushToken / DeviceType
        PushService::createOrUpdateByLoginIdentity($this->identity);
    }
}