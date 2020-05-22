<?php


namespace Twdd\Helpers\CalPrice;


use Twdd\Helpers\CalPrice\Longterm\PriceNormal;

class CalPriceLongterm extends AbstractCalPrice implements InterfaceCalPrice
{

    private function getClass(){

        return PriceNormal::class;
    }
    public function cal() : array{

        return app($this->getClass())->fire($this->settingPrice, $this->distance, $this->duration);
    }

}
