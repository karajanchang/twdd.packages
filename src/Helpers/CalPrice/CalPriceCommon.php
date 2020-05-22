<?php

namespace Twdd\Helpers\CalPrice;

use Twdd\Helpers\CalPrice\Common\PriceNormal;
use Twdd\Helpers\CalPrice\Common\PriceOver;
use Twdd\Helpers\CalPrice\Common\PriceShort;

class CalPriceCommon extends AbstractCalPrice implements InterfaceCalPrice
{

    private function getClass() : string{
        //--短程費用
        if(isset($this->settingPrice->short_mile) && $this->distance <= $this->settingPrice->short_mile){

            return PriceShort::class;
        }else {
            //--超過over_mile
            if (isset($this->settingPrice->over_mile) && $this->distance > $this->settingPrice->over_mile) {

                return PriceOver::class;
            }else{

                return PriceNormal::class;
            }
        }
    }

    public function cal() : array{
        $className = $this->getClass();

        return app($className)->fire($this->settingPrice, $this->distance);
    }

}
