<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-24
 * Time: 17:46
 */

namespace Zhyu\Ecpay\Invoice\Types;

use ArrayAccess;

abstract class TypeAbstract implements ArrayAccess
{
    protected $attributes = [];
    protected $Invoice_Method;
    protected $Invoice_Url;

    

    public function offsetExists($offset){

        return isset($this->attributes[$offset]);
    }
    public function offsetGet($offset){
        if(isset($this->attributes[$offset])) {

            return $this->attributes[$offset];
        }
    }
    public function offsetSet($offset, $value){
        if(isset($this->attributes[$offset])) {
            $this->attributes[$offset] = $value;
        }
    }
    public function offsetUnset($offset){
        if(isset($this->attributes[$offset])) {

            unset($this->attributes[$offset]);
        }

    }

    public function attributes(){
        return $this->attributes;
    }

    public function __set($key, $val){
        $this->attributes[$key] = $val;
    }

    public function __get($key){
        return $this->attributes[$key];
    }
}