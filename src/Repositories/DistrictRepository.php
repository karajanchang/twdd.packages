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
//        $whereIsopen = new WhereIsopen('district');
        $this->pushCriteria($joinCity);
//        $this->pushCriteria($whereIsopen);
        if(class_exists('Redis')) {
            $all = $this->allCache(['district.id as district_id', 'city_id', 'district.name as district', 'zip', 'city.name as city', 'district.isopen as isopen'], md5(__DIR__).'AllDistrict', 3600);
        }else{
            $all = $this->all(['district.id as district_id', 'city_id', 'district.name as district', 'zip', 'city.name as city', 'district.isopen as isopen']);
        }

        return $all;
    }

    public function locationFromZip($zip){
        $all = $this->allIsopen();
        $districts = $all->where('zip', substr($zip, 0, 3));

        return $districts;
    }

    public function locationFromDistrictId($district_id){
        $all = $this->allIsopen();
        $districts = $all->where('district_id', $district_id);

        return $districts;
    }


}
