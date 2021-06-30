<?php
namespace Twdd\Jobs\Login;

use App\Jobs\Job;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Log;
use Twdd\Mail\Login\FailMail;
use Twdd\Models\LoginIdentify;
use Illuminate\Support\Facades\Mail;

class LoginFailNotify extends Job
{
    private $identity;

    public function __construct(LoginIdentify $identity)
    {
        $this->identity = $identity;
    }

    public function handle(){
        if(isset($this->identity)) {

            if (!empty($this->identity->email)) {
                Mail::to($this->identity->email)->queue(new FailMail($this->identity));
            }

            //---通知上一個登入的裝置，你的帳號被從另一裝置登入
            try {
                dispatch(new PushNotify($this->identity, '登入失敗：駕駛帳號密碼有誤', '你的帳號登入失敗，若非你本人的操作請通知公司'));
            } catch (\Exception $e) {
                Bugsnag::notifyException($e);
                Log::error('gorush does not start', [$e]);
            }
        }
    }
}