<?php


namespace Twdd\Repositories;


use Illuminate\Database\Eloquent\Model;
use Twdd\Models\DriverCreditChange;
use Zhyu\Repositories\Eloquents\Repository;

class DriverCreditChangeRepository extends Repository
{
    public function model(){

        return DriverCreditChange::class;
    }

}