<?php


namespace Twdd\Services\Token;


use Twdd\Services\ServiceAbstract;

class TokenAbstract extends ServiceAbstract
{
    protected $accountField = null;
    protected $passwordField = null;
    protected $PushToken = null;
    protected $account = null;
    protected $password = null;
    protected $error = null;
    protected $params = [];

    public function account($account){
        $this->account = $account;
    }

    public function password($password){
        $this->password = $password;
    }

    public function PushToken($PushToken){
        $this->PushToken = $PushToken;
    }

    public function params(array $params){
        if(isset($params[$this->accountField])) {
            $this->account($params[$this->accountField]);
        }

        if(isset($params[$this->passwordField])) {
            $this->password($params[$this->passwordField]);
        }

        if(isset($params[$this->passwordField])) {
            $this->PushToken($params['PushToken']);
        }
        $this->params = $params;

        return $this;
    }

    public function identity(array $cols = ['*']){

        return $this->repository->findBy($this->accountField, $this->account, $cols);
    }

}