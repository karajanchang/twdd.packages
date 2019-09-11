<?php


namespace Twdd\Services\Token;


use Twdd\Services\ServiceAbstract;

class TokenAbstract extends ServiceAbstract
{
    protected $accountField = null;
    protected $passwordField = null;

    protected $emailColumn = null;
    protected $mobileColumn = null;
    protected $nameColumn = null;
    protected $pushColumn = null;
    protected $type = null;

    protected $PushToken = null;
    protected $DeviceType = null;
    protected $account = null;
    protected $password = null;
    protected $error = null;
    protected $params = [];
    protected $identity = null;

    public function setAccount($account){
        $this->account = $account;
    }

    public function getAccount(){

        return $this->account;
    }

    public function setPassword($password){
        $this->password = $password;
    }

    /**
     * @return null
     */
    public function getDeviceType()
    {
        return $this->DeviceType;
    }

    /**
     * @param null $DeviceType
     */
    public function setDeviceType($DeviceType): void
    {
        $this->DeviceType = $DeviceType;
    }

    public function setPushToken($PushToken){
        $this->PushToken = $PushToken;
    }

    /**
     * @return null
     */
    public function getPushToken()
    {
        return $this->PushToken;
    }


    /**
     * @return null
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @param null $identity
     */
    public function setIdentity($identity): void
    {
        $this->identity = $identity;
    }

    public function params(array $params){
        if(isset($params[$this->accountField])) {
            $this->setAccount($params[$this->accountField]);
        }

        if(isset($params[$this->passwordField])) {
            $this->setPassword($params[$this->passwordField]);
        }

        if(isset($params['PushToken'])) {
            $this->setPushToken($params['PushToken']);
        }

        if(isset($params['DeviceType'])) {
            $this->setDeviceType($params['DeviceType']);
        }

        $this->params = $params;

        return $this;
    }

    public function identity(array $cols = ['*']){
        return $this->repository->findBy($this->accountField, $this->account, $cols);
    }

    /**
     * @return null
     */
    public function getEmailColumn()
    {
        return $this->emailColumn;
    }

    /**
     * @return null
     */
    public function getMobileColumn()
    {
        return $this->mobileColumn;
    }

    /**
     * @return null
     */
    public function getNameColumn()
    {
        return $this->nameColumn;
    }

    /**
     * @return null
     */
    public function getPushColumn()
    {
        return $this->pushColumn;
    }

    /**
     * @return null
     */
    public function getType()
    {
        return $this->type;
    }


}