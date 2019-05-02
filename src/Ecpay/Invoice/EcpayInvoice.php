<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-23
 * Time: 23:04
 */
namespace Twdd\Ecpay\Invoice;

use Illuminate\Support\Collection;
use Twdd\Ecpay\CheckMac;
use Zhyu\Facades\ZhyuCurl;
use Illuminate\Support\Facades\Log;

class EcpayInvoice
{
    public $EcpayType = null;
    public $EcpayCheckMac = null;
    public $MerchantID = '3109792';
    public $HashKey = 'tE1Wxf77Sua3tvN6';
    public $HashIV = 'wo8z6Hht6HNEycGW';
    
    protected $adapter;
    protected $exception;


    protected $items = [];

    /**
     * EcpayInvoice constructor.
     */
    public function __construct($type)
    {
        $type = strtolower($type);
        $lut = include __DIR__.'/config.php';
        if(!isset($lut[$type])){
            Log::alert('This invoice type does not exists!', [$lut, $type]);
            throw new \Exception('This invoice type does not exists!');
        }
        $this->exception = new EcpayInvoiceException();;
		$config = Collection::make($lut)->get($type);
		if(isset($config['type'])) {
			$this->EcpayType = app()->make($config['type']);
		}

        if(env('APP_DEBUG')===true){
            $this->EcpayType->testing();
            $this->testing();
        }else{
            $this->production();
        }

	    if(isset($config['checkmac'])) {
		    $this->EcpayCheckMac = app()->make($config['checkmac'], [
			    'HashKey' => $this->HashKey,
			    'HashIV' => $this->HashIV,
		    ]);
	    }
        //$this->adapter = new EcpayInvoiceAdapter($this->MerchantID, $this->HashKey, $this->HashIV);
    }

    public function production(){
        $this->MerchantID = env('ECPAY_INVOICE_MERCHANTID', '2000132');
        $this->HashKey = env('ECPAY_INVOICE_HASHKEY', 'ejCk326UnaZWKisg');
        $this->HashIV = env('ECPAY_INVOICE_HASHIV', 'q9jcZX8Ib9LM8wYk');
    }

    public function testing(){
        $this->MerchantID = '2000132';
        $this->HashKey = 'ejCk326UnaZWKisg';
        $this->HashIV = 'q9jcZX8Ib9LM8wYk';
    }
    
    private function makeParams(){
    	$params = $this->EcpayType->attributes();
    	
    	$this->makeItems2Params($params);
    	
    	return $params;
    }
    private function makeItems2Params(&$params){
    	$items = $this->getItems();
    	$rparams = [];
    	$params['MerchantID'] = $this->MerchantID;
    	$rparams['ItemName'] = [];
	    $rparams['ItemCount'] = [];
	    $rparams['ItemWord'] = [];
	    $rparams['ItemPrice'] = [];
	    $rparams['ItemTaxType'] = [];
	    $rparams['ItemAmount'] = [];
	    $rparams['ItemRemark'] = [];
    	foreach($items as $item){
    		array_push($rparams['ItemName'], $item->getName());
		    array_push($rparams['ItemCount'], $item->getNums());
		    array_push($rparams['ItemWord'], $item->getWord());
		    array_push($rparams['ItemPrice'], $item->getPrice());
		    array_push($rparams['ItemAmount'], $item->getAmount());
		    array_push($rparams['ItemRemark'], $item->getRemark());
	    }

	    foreach($rparams as $key => $paras){
	        $params[$key] = join('|', $paras);
	    }

	    $this->EcpayCheckMac->setParams($params)
		                    ->fire();

        $params['CheckMacValue'] = (string) $this->EcpayCheckMac;

        return $params;
    }
    
    public function fire(){
        if($this->count()==0){
            return $this->exception->haveNoItems();
        }
        
        $params = $this->makeParams();

        $res = ZhyuCurl::url($this->EcpayType->Invoice_Url)->post($params);

        parse_str($res, $results);

        Log::info($res);

        return $results;
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
        $this->EcpayType->$key = $val;
    }

    public function __get($key){
        return $this->EcpayType->$key;
    }
    
}