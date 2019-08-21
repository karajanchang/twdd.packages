<?php
namespace Twdd\Services\Token;

use Twdd\Errors\MemberErrors;
use Twdd\Repositories\MemberRepository;


class MemberToken extends TokenAbstract implements InterfaceToken
{
    protected $accountField = 'UserPhone';
    protected $passwordField = 'UserPassword';

    public function __construct(MemberRepository $repository, MemberErrors $memberErrors)
    {
        $this->repository = $repository;
        $this->error = $memberErrors;
    }

    public function validate(){
        if(is_null($this->account)){

            return $this->error['1001'];
        }
        if(is_null($this->password)){

            return $this->error['1002'];
        }
        if(is_null($this->PushToken)){

            return $this->error['1003'];
        }

        return true;
    }

    public function login(){
        $res = $this->validate();
        if($res!==true){

            return $res;
        }

        $identity = $this->identity(['id', 'UserPhone', 'UserName', 'remember_token', 'is_online' ]);
        if(!isset($identity->id)){

            return $this->error['2003'];
        }
        
        return $identity;
    }

}