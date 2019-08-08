<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-07
 * Time: 19:03
 */

namespace Twdd\Errors;

use Error;
use ArrayAccess;
use Throwable;
use Illuminate\Support\Facades\Config;

class ErrorAbstract extends Error implements ArrayAccess
{
    protected $code = null;
    protected $message = null;
    protected $unit = null;
    protected $attributes;
    protected $replaces = [];

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $locale = Config::get('app.locale');

        $lang_path = base_path('lang/'.$locale.'/'.$this->unit.'/validation.php');
        if(!file_exists($lang_path)){
            $lang_path = base_path('vendor/twdd/packages/src/lang/'.$locale.'/'.$this->unit.'/validation.php');
        }
        $lang = include $lang_path;
        $this->setAttributes($lang['attributes']);
    }

    public function error101(){

        return trans('twdd::errors.validate_error');
    }

    public function setAttributes($attributes){
        $this->attributes = $attributes;
    }
    public function getAttributes(){
        return $this->attributes;
    }

    private function name($offset){

        return 'error'.$offset;
    }
    public function offsetExists($offset){
        if(method_exists($this, $this->name($offset))){

            return true;
        }

        return false;
    }
    public function offsetGet($offset){
        $tmp = $this->name($offset);
        $this->code = $offset;
        $this->message = $this->$tmp();

        return $this;
    }

    public function offsetSet($offset, $value){
        $this->code = $offset;
        $this->message = $value;

        return $this;
    }

    public function offsetUnset($offset){

    }

    /**
     * @return ErrorAbstract
     */
    public function getUnit()
    {
        return $this->unit;
    }

    public function setReplaces($tag ,$value = null){
        if(is_array($tag)){
           $this->replaces = $tag;
        }else {
            $this->replaces[$tag] = $value;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getReplaces($tag = null)
    {
        if(!is_null($tag)){
            $res = [];
            $res[$tag] = $this->replaces[$tag];

            return $res;
        }

        return $this->replaces;
    }

    public function error500(){

        return trans('twdd::errors.operation_error');
    }

}
