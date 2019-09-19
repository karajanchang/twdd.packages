<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Twdd\Models\MongoMemberLatLon;
use Zhyu\Repositories\Eloquents\Repository;

class MongoMemberLatLonRepository extends Repository
{

    public function model(){
        return MongoMemberLatLon::class;
    }
}
