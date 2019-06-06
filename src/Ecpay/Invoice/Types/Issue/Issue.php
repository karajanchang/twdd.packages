<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-24
 * Time: 06:39
 */
namespace Twdd\Ecpay\Invoice\Types\Issue;

use Twdd\Ecpay\Invoice\Types\TypeAbstract;
use Twdd\Ecpay\Invoice\Types\TypeInterface;

class Issue extends TypeAbstract implements TypeInterface
{
    public $Invoice_Url = 'https://einvoice.ecpay.com.tw/Invoice/Issue';

    public function __construct()
    {
        $this->TimeStamp = time();
        $this->Print = '1';
        $this->Donation = '0';
        $this->TaxType = '1';
        $this->InvType = '07';
    }

    public function testing(){
        $this->Invoice_Url = 'https://einvoice-stage.ecpay.com.tw/Invoice/Issue';
    }
}