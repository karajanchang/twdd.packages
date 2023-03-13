<?php 

namespace Twdd\Services\Invoice;

interface InvoiceInterface {

    public function issue();
    public function invalid();
}