<?php


namespace Twdd\Helpers;

use Illuminate\Support\Facades\App;
use Twdd\Services\Invoice\InvoiceInterface;
class InvoiceFactory
{
    public function bind(string $invoiceType)
    {
        
        $className = $invoiceType == "B2C" ? \Twdd\Services\Invoice\Type\B2C::class : \Twdd\Services\Invoice\Type\B2B::class;
       
        App::bind(InvoiceInterface::class, $className);
    }
}