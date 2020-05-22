<?php


namespace Twdd\Helpers\CalPrice;


abstract class AbstractCalPrice
{
    protected $distance;
    protected $duration;
    protected $settingPrice;

    public function __construct($settingPrice, float $distance, int $duration)
    {
        $this->distance = $distance;
        $this->settingPrice = $settingPrice;
        $this->duration = $duration;
    }
}
