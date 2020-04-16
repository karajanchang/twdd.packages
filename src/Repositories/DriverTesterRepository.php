<?php


namespace Twdd\Repositories;


use Twdd\Models\DriverTester;
use Zhyu\Repositories\Eloquents\Repository;

class DriverTesterRepository extends Repository
{
    public function model(){

        return DriverTester::class;
    }


}