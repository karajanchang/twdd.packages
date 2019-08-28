<?php


namespace Twdd\Helpers;


use Twdd\Repositories\DistrictRepository;

class LatLonService
{
    private $repository;

    public function __construct()
    {
        $this->repository = app()->make(DistrictRepository::class);

        return $this;
    }

    public function citydistrictFromZip($zip){

        return $this->repository->citydistrictFromZip($zip);
    }




}
