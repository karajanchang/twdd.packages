<?php

namespace Twdd\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseStaffs extends Model
{
    use SoftDeletes;

    protected $table = 'enterprise_staffs';

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'enterprise_id',
        'tel',
        'position',
        'enable',
        'created_at',
        'updated_at',
        'website_permissions',
        'service_permission',
        'department',
        'staff_code'
    ];

    protected $hidden = [
        'password',
    ];

    public $websitePermissions = [
        'access_history',
        'access_report',
        'access_staffs_setting',
        'access_enterprise_info'
    ];

    public $servicePermission = [
        1 => '不可使用',
        2 => '企業折扣(自費)',
        3 => '企業月結(公司付款)'
    ];


    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function member()
    {
        return $this->hasOne(Member::class, 'UserPhone', 'mobile');
    }

}