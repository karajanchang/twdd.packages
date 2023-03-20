<?php


namespace Twdd\Helpers;

use Illuminate\Support\Facades\App;
use Twdd\Services\Invoice\InvoiceInterface;
use Twdd\Services\Invoice\Type\B2C;
use Twdd\Services\Invoice\Type\B2B;

class InvoiceFactory
{
    public function bind(string $invoiceType)
    {
        
        $className = $invoiceType == "B2C" ? B2C::class : B2B::class;
       
        App::bind(InvoiceInterface::class, $className);
    }
}