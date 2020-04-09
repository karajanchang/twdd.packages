<?php
namespace Twdd\Services\Token;

use Twdd\Errors\MemberErrors;
use Twdd\Repositories\MemberRepository;


class MemberToken extends TokenAbstract implements InterfaceToken
{
    protected $accountField = 'UserPhone';
    protected $passwordField = 'UserPassword';
    protected $emailColumn = 'UserEmail';
    protected $mobileColumn = 'UserPhone';
    protected $nameColumn = 'UserName';
    protected $pushColumn = 'memberpush';
    protected $type = 'member';

    public function __construct(MemberRepository $repository, MemberErrors $memberErrors)
    {
        $this->repository = $repository;
        $this->error = $memberErrors;
    }

    /*
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
    */

    public function login(){
        $res = $this->validate($this->params);
        if($res!==true){

            return $res;
        }

        if(is_null($this->PushToken)){

            return $this->error->_('1003');
        }

        $identity = $this->identity(['id', 'UserPhone', 'UserName', 'UserPassword', 'UserEmail', 'remember_token', 'is_online' ]);
        $this->setIdentity($identity);
        if(!isset($identity->id)){

            return $this->error['2003'];
        }

        //---登入失敗
        if(md5($this->params['UserPassword'])!=$identity->UserPassword){

            return $this->error->_('1011');
        }

        if($identity->is_online!=1){

            return $this->error->_('1005');
        }

        return $identity;
    }

    public function rules(){

        return [
            'UserPhone' => 'required',
            'UserPassword' => 'required',
            'DeviceType' => 'required',
            'PushToken' => 'required',
            'PushEnv' => 'string',
            'ID' => 'required|string'
        ];
    }
}