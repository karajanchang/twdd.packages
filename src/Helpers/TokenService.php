<?php


namespace Twdd\Helpers;


use Twdd\Services\Token\DriverToken;
use Twdd\Services\Token\MemberToken;

class TokenService
{
    private $service;
    public function driver(){
        $this->service = app()->make(DriverToken::class);
        
        return $this;
    }
    public function member(){
        $this->service = app()->make(MemberToken::class);
        
        return $this;
    }
    public function login(array $params){
        
        return $this->service->params($params)->login();
    }
}