<?php


namespace Twdd\Repositories;

use Twdd\Models\DriverExtraCredit;
use Illuminate\Database\Eloquent\Model;
use Zhyu\Repositories\Eloquents\Repository;

class DriverExtraCreditRepository extends Repository
{
    public function model()
    {
        return DriverExtraCredit::class;
    }
}