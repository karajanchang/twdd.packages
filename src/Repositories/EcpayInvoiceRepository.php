<?php

namespace Twdd\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Twdd\Models\EcpayInvoice;
use Zhyu\Repositories\Eloquents\Repository;

class EcpayInvoiceRepository extends Repository
{
    public function model()
    {
        return EcpayInvoice::class;
    }

    public function softDelete($id)
    {
        return $this->model->where('id',$id)->delete();
    }

    
}
