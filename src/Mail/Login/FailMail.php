<?php

namespace Twdd\Mail\Login;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Zhyu\Facades\Ip;
use Twdd\Models\LoginIdentify;

class FailMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(LoginIdentify $model)
    {
        $this->model = $model;
    }

    public function build(){
        $ip = Ip::get();

        return $this->view('emails.login.fail')
            ->subject('安全通知，你的帳戶被嘗試登入失敗')
            ->with([
                'name' => $this->model->name,
                'ip' => $ip,
            ]);
    }

}