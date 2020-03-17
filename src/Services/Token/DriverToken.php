<?php
namespace Twdd\Services\Token;

use Twdd\Errors\DriverErrors;
use Twdd\Repositories\DriverRepository;

class DriverToken extends TokenAbstract implements InterfaceToken
{
    protected $accountField = 'DriverID';
    protected $passwordField = 'DriverPassword';
    protected $emailColumn = 'DriverEmail';
    protected $mobileColumn = 'DriverPhone';
    protected $nameColumn = 'DriverName';
    protected $pushColumn = 'driverpush';
    protected $type = 'driver';


    public function __construct(DriverRepository $repository, DriverErrors $driverErrors)
    {
        $this->repository = $repository;
        $this->error = $driverErrors;
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
        $identity = $this->identity(['id', 'DriverID', 'DriverName', 'DriverPassword', 'DriverEmail',  'DriverPhone', 'remember_token', 'is_online', 'is_out', 'DriverCredit', 'DriverNew', 'is_pass_rookie', 'DriverServiceTime', 'pass_rookie_times']);
        $this->setIdentity($identity);

        if(!isset($identity->id)){

            return $this->error->_('2003');
        }

        //---登入失敗
        if(md5($this->params['DriverPassword'])!=$identity->DriverPassword){

            return $this->error->_('1011');
        }

        if($identity->is_online!=1){

            return $this->error->_('1005');
        }

        if($identity->is_out==1){

            return $this->error->_('1006');
        }

        if($identity->DriverNew<2){

            return $this->error->_('1008');
        }

        /*
        if($identity->is_pass_rookie==0 && $identity->isARookie()===true){

            return $this->error->_('1009', [
                'start' => env('OLDBIRD_HOUR_START', 1),
                'end' => env('OLDBIRD_HOUR_END', 5),
                'nums' => ($identity->pass_rookie_times - $identity->DriverServiceTime),
            ]);
        }
        */

        if($identity->is_tmp_offline===true){

            return $this->error->_('1010');
        }


        return $identity;
    }

    public function rules(){

        return [
            'DriverID' => 'required|size:9',
            'DriverPassword' => 'required|min:6',
            'DeviceType' => 'required',
            'PushToken' => 'required',
            'ID' => 'required|string'
        ];
    }

}