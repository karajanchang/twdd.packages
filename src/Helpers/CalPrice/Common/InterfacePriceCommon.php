<?php


namespace Twdd\Helpers\CalPrice\Common;


interface InterfacePriceCommon
{
    public function fire($settingPrice, float $distance) : array;
}
