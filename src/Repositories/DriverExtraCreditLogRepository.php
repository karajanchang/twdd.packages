<?php


namespace Twdd\Repositories;

use Twdd\Models\DriverExtraCreditLog;
use Illuminate\Database\Eloquent\Model;
use Zhyu\Repositories\Eloquents\Repository;

class DriverExtraCreditLogRepository extends Repository
{
    public function model()
    {
        return DriverExtraCreditLog::class;
    }
}