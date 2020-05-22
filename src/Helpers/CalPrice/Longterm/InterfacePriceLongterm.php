<?php


namespace Twdd\Helpers\CalPrice\Longterm;


interface InterfacePriceLongterm
{
    public function fire($settingPrice, float $distance, int $duration) : array;
}
