<?php


namespace Twdd\Repositories;


use Twdd\Models\DriverPush;
use Zhyu\Repositories\Eloquents\Repository;

class DriverPushRepository extends Repository
{
    public function model(){

        return DriverPush::class;
    }

    public function checkIfIsByPushEnv(string $PushToken){
        //--正式機不切換IOS port
        if(env('APP_TYPE', 'development')=='production'){

            return false;
        }
        $push = $this->where('PushToken', $PushToken)->where('DeviceType', 'iPhone')->select('PushEnv')->get();
        if(!empty($push->PushEnv) && strlen($push->PushEnv) > 0 ){

            return $push->PushEnv;
        }

        return false;
    }
}