<?php

namespace Twdd\Models;

use Illuminate\Database\Eloquent\Model;

class Enterprise extends Model
{

    public $table="enterprises";
    public $statusOptions = [
        '0' => '未審核',
        '1' => '已開通',
        '2' => '未通過',
    ];

    public $peopleNumsOptions = [
        '1' => '01~10人',
        '2' => '11~20人',
        '3' => '21~50人',
        '4' => '51~80人',
        '5' => '80人以上',
    ];

    protected $fillable = [
        'title',
        'contact_staff_id',
        'account_staff_id',
        'GUI_number',
        'status',
        'address',
        'people_nums',
        'verified_at',
        'enable'
    ];

    protected $guarded = ['id'];

    public function mainContact()
    {
        return $this->hasOne(EnterpriseStaffs::class, 'id', 'contact_staff_id');
    }

    public function accountContact()
    {
        return $this->hasOne(EnterpriseStaffs::class, 'id', 'account_staff_id');
    }
}

    

