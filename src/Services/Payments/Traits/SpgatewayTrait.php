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
        // empty call_member_id 等同於自己叫自己，不要問為什麼，我也想知道
        if ($this->task->type == 1 && $this->task->call_type == 3 && !empty($this->task->call_member_id)) {
            $this->memberCreditCard = app(MemberCreditcardRepository::class)->defaultCreditCard($this->task->call_member_id);
        } else {
            $this->memberCreditCard = app(MemberCreditcardRepository::class)->findByTask($this->task);
        }
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
