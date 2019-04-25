<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-23
 * Time: 23:19
 */

class EcpayInvoiceItem
{
    private $name = '';
    private $nums = 0;
    private $price = 0;
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
    public function setName($name): void
    {
        $this->name = $name;
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
    public function setNums($nums): void
    {
        $this->nums = $nums;
        $this->amount();
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
    public function setPrice($price): void
    {
        $this->price = $price;
        $this->amount();
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
    public function setRemark($remark): void
    {
        $this->remark = $remark;
    }

    private function amount(){
        $this->amount = $this->price * $this->nums;
    }

}