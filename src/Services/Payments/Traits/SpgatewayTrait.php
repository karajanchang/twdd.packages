<?php


namespace Twdd\Services\Payments\Traits;


use Illuminate\Database\Eloquent\Model;
use Twdd\Repositories\DriverMerchantRepository;
use Twdd\Repositories\MemberCreditcardRepository;

Trait SpgatewayTrait
{
    use SpgateCreditCardTrait;

    private $memberCreditCard;




    protected function preInit(){
        $this->driverMerchant = app(DriverMerchantRepository::class)->findByTask($this->task);
        $this->memberCreditCard = app(MemberCreditcardRepository::class)->findByTask($this->task);
    }

    /**
     * @return mixed
     */
    public function getMemberCreditCard()
    {
        return $this->memberCreditCard;
    }

    /**
     * @param mixed $memberCreditCard
     */
    public function setMemberCreditCard(Model $memberCreditCard)
    {
        $this->memberCreditCard = $memberCreditCard;

        return $this;
    }

    private function checkIfMemberCreditcardExists(){
        $memberCreditCard = $this->getMemberCreditCard();
        if(empty($memberCreditCard->id)){

            return false;
        }

        return true;
    }


}
