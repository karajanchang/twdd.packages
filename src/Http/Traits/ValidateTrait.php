<?php
namespace Twdd\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

Trait ValidateTrait
{
    use ControllerOutputTrait;

    public function getParams(){
        $request = app(Request::class);
        $params = $request->input("params");
        $attributes = $request->input("attributes");
        $pars = $params;
        if(is_array($attributes)) {
            $pars = array_merge($params, $attributes);
        }
        return $pars;
    }

    public function valid(array $rules, $request = null){
        if(count($rules)==0) return true;

        if(is_array($request)){
            $pars = $request;
        }else{
            $pars = $this->getParams();
        }

        $validate = Validator::make($pars, $rules);
        if($validate->fails()){
            $msgs = $validate->messages();

            return $this->validError($msgs);
        }

        return true;
    }

    private function validError($msgs){
        return [
            'code' => -1,
            'msg' => trans('twdd::errors.validate_error'),
            'return' => null,
            'error' => [
                'code' => 101,
                'message' => $msgs,
            ],
        ];
    }
}