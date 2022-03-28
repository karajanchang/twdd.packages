<?php


namespace Twdd\Repositories;

use Twdd\Models\DriverCreditChangeType;
use Illuminate\Database\Eloquent\Model;
use Zhyu\Repositories\Eloquents\Repository;

class DriverCreditChangeTypeRepository extends Repository
{
    /**
     * 允許額外收費的類型
     */
    const ALLOW_EXTRA_CREDIT = [
        'insurance'
    ];

    /**
     * 正數為贈與
     * 負數為加收
     */
    const EXTRA_CREDIT_PLUS_MINUS = [
        'insurance' => -1
    ];

    public function model()
    {
        return DriverCreditChangeType::class;
    }
}