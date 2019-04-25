<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-23
 * Time: 23:04
 */
namespace Twdd\Ecpay\Invoice;

use ArrayAccess;
use Countable;

include_once __DIR__.'/../sdk/Ecpay_Invoice.php';

class EcpayInvoice implements Countableccess
{
    private $testing = true;
    public $EcpayType = null;
    public $MerchantID = '3109792';
    public $HashKey = 'tE1Wxf77Sua3tvN6';
    public $HashIV = 'wo8z6Hht6HNEycGW';
    
    protected $adapter;
    protected $exception;


    protected $items = [];

    /**
     * EcpayInvoice constructor.
     */
    public function __construct($type, EcpayInvoiceException $exception )
    {
        $type = strtolower($type);
        $lut = include_once __DIR__.'/config.php';
        if(!isset($lut[$type])){
            throw new \Exception('This invoice type does not exists!');
        }
        $this->exception = $exception;

        $this->EcpayType = app()->make( Collection::make($lut)->get($type) );

        if($this->testing===true){
            $this->EcpayType->testing();
            $this->testing();
        }
        $this->adapter = new EcpayInvoiceAdapter($this->MerchantID, $this->HashKey, $this->HashIV);
    }


    public function testing(){
        $this->MerchantID = '2000132';
        $this->HashKey = 'ejCk326UnaZWKisg';
        $this->HashIV = 'q9jcZX8Ib9LM8wYk';
    }
    

    public function fire(){
        if($this->count()==0){
            return $this->exception->haveNoItems();
        }
        $this->adapter->setType($this->type);
        $this->adapter->setItems($this->getItems());

        return $this->adapter->fire();
    }

    public function pushItem(EcpayInvoiceItem $item){
        $this->items[] = $item;
    }
    public function getItems(){

        return $this->items;
    }

    public function count(){
        return count($this->items);
    }

    public function __set($key, $val){
        $this->EcpayType->attributes[$key] = $val;
    }

    public function __get($key){
        return $this->EcpayType->attributes[$key];
    }
    
}