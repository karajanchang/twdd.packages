<?php


namespace Twdd\Helpers;


class Twdd
{
    public function __call($name, $arguments)
    {
        if(isset($arguments[0])){

            return call_user_func($name, $arguments[0]);
        }

    }

}