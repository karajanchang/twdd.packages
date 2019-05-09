<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 12:19
 */

namespace Twdd\Traits;


Trait AttributesArrayTrait
{
    private $attributes = [];

    public function offsetExists($offset){
        if(isset($this->attributes[$offset])){

            return true;
        }

        return false;
    }


    public function offsetGet($offset){
        if(isset($this->attributes[$offset])){
            return $this->attributes[$offset];
        }

        return null;
    }


    public function offsetSet($offset, $value){
        $this->attributes[$offset] = $value;
    }


    public function offsetUnset($offset){
        unset($this->attributes[$offset]);
    }

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function __get($name){
        return $this->attributes[$name];
    }

    public function toArray()
    {
        return $this->attributes;
    }
}