<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-06
 * Time: 18:07
 */

namespace Twdd\Repositories;


use Twdd\Criterias\JoinCity;
use Twdd\Criterias\WhereIsopen;
use Twdd\Models\District;
use Zhyu\Repositories\Eloquents\Repository;

class DistrictRepository extends Repository
{

    public function model(){
        return District::class;
    }

    public function allIsopen(){
        $joinCity = new JoinCity();
        $whereIsopen = new WhereIsopen('district');
        $this->pushCriteria($joinCity);
        $this->pushCriteria($whereIsopen);
        $all = $this->allCache([ 'district.id as district_id', 'city_id', 'district.name as district', 'zip', 'city.name as city' ], 'AllDistrict', 3600);

        return $all;
    }

    public function citydistrictFromZip(int $zip){
        $all = $this->allIsopen();

        $districts = $all->where('zip', $zip);

        return $districts;
    }


}
