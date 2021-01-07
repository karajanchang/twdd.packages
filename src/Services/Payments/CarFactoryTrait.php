<?php


namespace Twdd\Services\Payments;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twdd\Events\SpgatewayErrorEvent;
use Twdd\Repositories\CarFactoryCreditcardRepository;
use Twdd\Repositories\DriverMerchantRepository;

trait CarFactoryTrait
{
    use SpgateCreditCardTrait;

    private $carFactoryCreditCard;
    private $seconds = 30;

    protected function preInit(){
        $this->driverMerchant = app(DriverMerchantRepository::class)->findByTask($this->task);
        $this->carFactoryCreditCard = app(CarFactoryCreditcardRepository::class)->findByTask($this->task);
    }

    /**
     * @return mixed
     */
    public function getCarFactoryCreditCard()
    {
        return $this->carFactoryCreditCard;
    }

    /**
     * @param mixed $carFactoryCreditCard
     */
    public function setCarFactoryCreditCard($carFactoryCreditCard)
    {
        $this->carFactoryCreditCard = $carFactoryCreditCard;

        return $this;
    }

    private function checkIfCarFactoryCreditcardExists(){
        $carFactoryCreditCard = $this->getCarFactoryCreditCard();
        if(empty($carFactoryCreditCard->id)){

            return false;
        }

        return true;
    }



}