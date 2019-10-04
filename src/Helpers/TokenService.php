<?php


namespace Twdd\Helpers;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Twdd\Errors\ErrorAbstract;
use Twdd\Http\Traits\ValidateTrait;
use Twdd\Jobs\Login\LoginFailNotify;
use Twdd\Models\LoginIdentify;
use Twdd\Services\Token\DriverToken;
use Twdd\Services\Token\GenerateToken;
use Twdd\Services\Token\MemberToken;
use Twdd\Jobs\Login\LoginSuccessNotify;

class TokenService
{
    use ValidateTrait;

    private $service;
    private $generateToken;

    /**
     * TokenService constructor.
     * @param $generateToken
     */
    public function __construct(GenerateToken $generateToken)
    {
        $this->generateToken = $generateToken;
    }


    public function driver(){
        $this->service = app()->make(DriverToken::class);

        return $this;
    }

    public function member(){
        $this->service = app()->make(MemberToken::class);

        return $this;
    }


    public function login(){
        $params = $this->getParams();

        $res = $this->service->params($params)->login();
        if(isset($res['error'])) {
            if ($res['error'] instanceof ErrorAbstract) {

                return $res;
            }
        }
        $identity = $this->service->getIdentity();

        /* 1.===================================================
        if(isset($res['error'])){
            Mail::to($identity->{$this->emailColumn})->queue(new \Twdd\Mail\Login\FailMail($identity));

        }else{
            Mail::to($identity->{$this->emailColumn})->queue(new \Twdd\Mail\Login\SuccessMail($identity));
        }
        */

        /* 2.===================================================
            $this->notify($res, $identity);
        */

        /* 3.===================================================
        */
        if(!isset($identity->id)) {

            return $res;
        }

        $loginIdentity = $this->loginIdentity($identity);
        if(isset($res['error'])){
            dispatch(new LoginFailNotify($loginIdentity));
        }else{
            dispatch(new LoginSuccessNotify($loginIdentity));
            $res = $this->generateToken->generate($loginIdentity);

            return $res;
        }

    }

    /*
    private function notify($res, $identity){
        if(isset($res['error'])){
            Mail::to($identity->{$this->emailColumn})->queue(new \Twdd\Mail\Login\FailMail($identity));
        }else{
            Mail::to($identity->{$this->emailColumn})->queue(new \Twdd\Mail\Login\SuccessMail($identity));
        }
    }
    */

    private function loginIdentity(Model $identity) :LoginIdentify{
        $loginIdentity = new LoginIdentify();
        $loginIdentity->id = $identity->id;
        $loginIdentity->email = $identity->{$this->service->getEmailColumn()};
        $loginIdentity->mobile = $identity->{$this->service->getMobileColumn()};
        $loginIdentity->name = $identity->{$this->service->getNameColumn()};
        $loginIdentity->type = $this->service->getType();
        $loginIdentity->PushToken = $this->service->getPushToken();
        $loginIdentity->DeviceType = $this->service->getDeviceType();
        $pushColumn = $this->service->getPushColumn();
        if(isset($identity->{$pushColumn})) {
            $loginIdentity->push = $identity->{$pushColumn};
        }

        return $loginIdentity;
    }

    //---依照Token取得id
    public function id(){

        return $this->generateToken->id();
    }
}