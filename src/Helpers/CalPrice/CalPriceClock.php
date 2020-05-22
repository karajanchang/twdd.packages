<?php


namespace Twdd\Helpers\CalPrice;


class CalPriceClock extends AbstractCalPrice implements InterfaceCalPrice
{
    public function cal() : array{

        return app($this->getClass())->fire($this->settingPrice, $this->distance, $this->duration);
    }
}