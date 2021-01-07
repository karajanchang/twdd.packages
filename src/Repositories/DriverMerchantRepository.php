<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Illuminate\Database\Eloquent\Model;
use Twdd\Models\DriverMerchant;
use Zhyu\Repositories\Eloquents\Repository;

class DriverMerchantRepository extends Repository
{

    public function model()
    {
        return DriverMerchant::class;
    }

    public function findByTask(Model $task){
        $model = $this->findby('driver_id', $task->driver_id);

        return $model;
    }

    public function findByDriverId(int $driver_id){
        $model = $this->findby('driver_id', $driver_id);

        return $model;
    }
}