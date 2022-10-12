<?php


namespace Twdd\Http\Traits;


use Illuminate\Support\Facades\Lang;
use Twdd\Errors\ErrorAbstract;

Trait ControllerOutputTrait
{
    public function error($msg = null, $obj = null, int $code = -1){
        return $this->output($code, $msg, $obj);
    }

    public function success($msg = null, $obj = null){

        return $this->output(0, $msg, $obj);
    }

    public function output($code = null, $msg = null, $obj = null, $valid = []){
        if(is_null($code)){
            $code = 0;
        }

        $pre = $code==0 ?   Lang::get('messages.success')   :   Lang::get('messages.error');

        $rObj = new \stdClass();
        $rObj->code = $code;
        $rObj->msg = $pre;
        $rObj->return = null;

        if(is_string($msg)) {
            $msg = is_null($msg) ?  $pre :   Lang::get($msg);
            $rObj->msg = $msg;
            $rObj->validate = $valid;
        }elseif(is_array($msg)){
            if(isset($msg['error'])){
                $errorObj = $msg['error'];
                if(is_array($errorObj)){

                    return $msg;
                }
                $rObj->msg = $errorObj->getMessage();
                //$error = new \stdClass();
                $error = [];
                $error['code'] = (string) $errorObj->getCode();
                $error['unit'] = (string) $errorObj->getUnit();
                $error['message'] = $errorObj->getMessage();

                $rObj->error = $error;
            }
            if(isset($msg['msg'])){
                $valid = $msg['msg'];
                $rObj->validate = $valid;
            }

        }
        if(!is_null($obj)) {
            $rObj->return = $obj;
        }

        return response()->json($rObj);

    }
}