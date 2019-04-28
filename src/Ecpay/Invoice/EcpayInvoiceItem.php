<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-23
 * Time: 23:19
 */
namespace Twdd\Ecpay\Invoice;

class EcpayInvoiceItem
{
    private $name = '';
    private $nums = 0;
    private $price = 0;
    private $word;
    private $remark = '';
    private $amount = 0;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): EcpayInvoiceItem
    {
        $this->name = $name;
        
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNums()
    {
        return $this->nums;
    }

    /**
     * @param mixed $nums
     */
    public function setNums($nums): EcpayInvoiceItem
    {
        $this->nums = $nums;
        $this->amount();
        
	    return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): EcpayInvoiceItem
    {
        $this->price = $price;
        $this->amount();
        
	    return $this;
    }

    /**
     * @return mixed
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param mixed $remark
     */
    public function setRemark($remark): EcpayInvoiceItem
    {
        $this->remark = $remark;
	
	    return $this;
    }
	
	/**
	 * @return mixed
	 */
	public function getWord() {
		return $this->word;
	}
	
	/**
	 * @param mixed $word
	 */
	public function setWord($word) {
		$this->word = $word;
		
		return $this;
	}
    
    

    private function amount(){
        $this->amount = $this->price * $this->nums;
    }
	
	/**
	 * @return int
	 */
	public function getAmount(): int {
		return $this->amount;
	}
    

}