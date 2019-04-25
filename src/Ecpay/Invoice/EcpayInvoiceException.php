<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-25
 * Time: 11:31
 */
namespace Twdd\Ecpay\Invoice;

use Exception;

class EcpayInvoiceException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {

        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function setException(string $message = '', $code = 0, $previous = null){
        parent::__construct($message, $code, $previous);
    }

    public function haveNoItems(){
        $this->setException('items必需要至少有一件', 101);

        return $this;
    }
}