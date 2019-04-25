<?php
namespace Twdd\Ecpay\Invoice;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Log;
use Twdd\Ecpay\Invoice\Types\TypeInterface;
use Exception;

/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-23
 * Time: 23:04
 */

include_once __DIR__.'/../sdk/Ecpay_Invoice.php';

class EcpayInvoiceAdapter
{
    private $ecpay_invoice;
    //private $type;
//    private $items = [];

    public function __construct($MerchantID, $HashKey, $HashIV)
    {
        $this->ecpay_invoice = new EcpayInvoice;
        $this->ecpay_invoice->MerchantID = $MerchantID;
        $this->ecpay_invoice->HashKey = $HashKey;
        $this->ecpay_invoice->HashIV = $HashIV;
    }

    public function setType(TypeInterface $type){
        //$this->type = $type;

        $this->ecpay_invoice->Invoice_Method = $type['Invoice_Method'];
        $this->ecpay_invoice->Invoice_Url = $type['Invoice_Url'];

        $this->ecpay_invoice->Send = $type['attributes'];
    }

    public function setItems(array $items){
        $this->ecpay_invoice->Send['Items'] = $items;
    }

    public function fire(){
        try {

            return $this->ecpay_invoice->Check_Out();
        }catch(Exception $e){
            Bugsnag::notifyException($e);
            Log::error('EcpayInvoiceAdapter error:'.$e->getMessage());
        }
    }

}