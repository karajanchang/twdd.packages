<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 12:24
 */

namespace Twdd\Services;

use Illuminate\Support\Facades\Config;
use Validator;

class ServiceAbstract
{
    private function messages($rules){
        $locale = Config::get('app.locale');
        $lang_path = base_path('vendor/twdd/packages/src/lang/'.$locale.'/validation.php');
        $trans = include $lang_path;

        $attributes = $this->error->getAttributes();

        $msgs = [];
        foreach($rules as $rule => $contains){
            if(isset($attributes[$rule])){
                if(strstr($contains,'|')){
                    $items = explode('|', $contains);
                }else{
                    $items[] = $contains;
                }
                foreach($items as $item){
                    $item = trim($item);
                    if($item=='nullable') continue;

                    if(strstr($item, ':')){
                        $exs = explode(':', $item);
                        $item = $exs[0];
                    }

                    $tmp = $rule.'.'.$item;
                    $msgs[$tmp] = str_replace(':attribute', $attributes[$rule], $trans[$item]);
                }
            }
        }

        return $msgs;
    }

    public function validate(array $params){
        $rules = $this->rules();
        $messages = $this->messages($rules);
        $validator = Validator::make($params, $rules, $messages);

        if($validator->fails()){
            $msg = $validator->messages();

            return [
                'error' =>  $this->error['101'],
                'msg' => $msg,
            ];
        }

        return true;
    }

}
