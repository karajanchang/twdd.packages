<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseStaff extends Model
{
    use SoftDeletes;
    public $table="enterprise_staffs";

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class,'enterprise_id', 'id');
    }
}