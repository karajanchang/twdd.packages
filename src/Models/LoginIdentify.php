<?php

namespace Twdd\Models;

use ArrayAccess;

class LoginIdentify implements ArrayAccess
{
    private $attributes = [];


    public function offsetExists($offset){

        return isset($this->attributes[$offset]);
    }
    public function offsetGet($offset){
        if(isset($this->attributes[$offset])) {

            return $this->attributes[$offset];
        }
    }
    public function offsetSet($offset, $value){
        $this->attributes[$offset] = $value;
    }
    public function offsetUnset($offset){
        if(isset($this->attributes[$offset])) {
            unset($this->attributes[$offset]);
        }
    }


    public function __set($key, $val){
        $this->offsetSet($key, $val);
    }

    public function __get($key){
        return $this->offsetGet($key);
    }
}