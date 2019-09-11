<?php


namespace Twdd\Repositories;


use Twdd\Models\DriverPush;
use Zhyu\Repositories\Eloquents\Repository;

class DriverPushRepository extends Repository
{
    public function model(){

        return DriverPush::class;
    }
}