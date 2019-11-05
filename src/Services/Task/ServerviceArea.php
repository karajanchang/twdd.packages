<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 16:05
 */
namespace Twdd\Services\Task;

use Twdd\Errors\TaskErrors;
use Twdd\Facades\GoogleMap;
use Twdd\Repositories\DistrictRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\AttributesArrayTrait;

class ServerviceArea extends ServiceAbstract
{
    use AttributesArrayTrait;

    public function __construct(DistrictRepository $repository, TaskErrors $taskErrors)
    {
        $this->repository = $repository;
        $this->error = $taskErrors;
    }

    public function check(array $params){
        $error = $this->validate($params);
        if($error!==true){
            return $error;
        }

        if($this->checkLatLon($params)!==true){

            return $this->error->_('1001');
        }

        $res = $this->checkZipIsOpen($params);
        if($res!==true){
            return $res;
        }

        return true;
    }

    private function checkLatLon(array $params){
        $lat = $params['lat'];
        $lon = $params['lon'];

        if($lat>25.29 || $lat<21.5350){

            return false;
        }
        if($lon>121.995 || $lon<119.86){

            return false;
        }

        return true;
    }

    private function checkZipIsOpen(array $params){
        if(!isset($params['zip'])){
            $location = GoogleMap::latlon($params['lat'], $params['lon']);
            $zip = $location['zip'];
        }else{
            $zip = $params['zip'];
        }

        $zip = substr($zip, 0, 3);

        if(strlen($zip)!=3){

            return $this->error->_('1002');
        }
        $districts = $this->repository->allIsopen();
        $district = $districts->where('zip', $zip);
        if(count($district)==0){

            return $this->error->_('1003');
        }

        return true;
    }

    public function rules(){
        return [
            'lat'              =>  'required',
            'lon'              =>  'required',
            'zip'              =>  'nullable|integer',
        ];
    }
}
