<?php
namespace Twdd\Services\Driver;

use Twdd\Repositories\DriverRepository;

class DriverState
{
    private $repository;

    public function __construct(DriverRepository $repository)
    {
        $this->repository = $repository;
    }

    public function changeDriverState(int $id, int $DriverState){
        $res = $this->repository->updateDriverState($id, $DriverState);

        return $res;
    }

}