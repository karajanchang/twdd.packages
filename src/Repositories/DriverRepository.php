<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;

use Twdd\Models\Driver;
use Zhyu\Repositories\Eloquents\Repository;

class DriverRepository extends Repository
{

    public function model()
    {
        return Driver::class;
    }

    public function updateDriverState(int $id, int $DriverState){

        return $this->update($id, [
            'DriverState' => $DriverState,
        ]);
    }

    public function updateDriverPassword(int $id, string $DriverPassword){

        return $this->update($id, [
            'DriverState' => $DriverPassword,
        ]);
    }

}