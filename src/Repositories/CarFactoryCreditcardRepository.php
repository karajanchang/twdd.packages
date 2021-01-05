<?php


namespace Twdd\Repositories;


use Twdd\Models\CarFactory;
use Zhyu\Repositories\Eloquents\Repository;

class CarFactoryCreditcardRepository extends Repository
{
    public function model()
    {
        return CarFactory::class;
    }



}