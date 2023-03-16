<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class EcpayInvoice extends Model
{

    public $table="ecpay_invoice";

    protected $fillable = [
        'relate_number',
        'invoice_number',
        'invoice_amount',
        'invoice_type',
        'calldriver_task_map_id',
        'task_id',
        'enterprise_bill_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $guarded = ['id'];

    use SoftDeletes;
}

    

