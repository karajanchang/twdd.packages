<?php


namespace Twdd\Repositories;


use Twdd\Models\Activity;
use Zhyu\Repositories\Eloquents\Repository;

class ActivityRepository extends Repository
{
    public function model()
    {
        return Activity::class;
    }

}