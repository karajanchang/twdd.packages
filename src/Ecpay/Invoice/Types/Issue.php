<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-24
 * Time: 06:39
 */
namespace Twdd\Ecpay\Invoice\Types;

class Issue extends TypeAbstract implements TypeInterface
{
    protected $Invoice_Method = 'INVOICE';
    protected $Invoice_Url = 'https://einvoice-stage.ecpay.com.tw/Invoice/Issue';

    public function __construct()
    {
        $this->Timestamp = time();
        $this->Print = '0';
        $this->Donation = '0';
        $this->TaxType = '0';
        $this->InvType = '07';

    }

    public function testing(){
        $this->Invoice_Url = 'https://einvoice-stage.ecpay.com.tw/Invoice/DelayIssue';
    }
}