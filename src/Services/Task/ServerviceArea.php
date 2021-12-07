<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 16:05
 */
namespace Twdd\Services\Task;

use Illuminate\Support\Facades\Log;
use Jyun\Mapsapi\TwddMap\Geocoding;
use Mtsung\TwddLocation\Facade\TwddLocation;
use Twdd\Errors\TaskErrors;
use Twdd\Facades\LatLonService;
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

    public function check(array $params, bool $is_skip_check_open_zip = false){
        $error = $this->validate($params);
        if($error!==true){
            return $error;
        }

        if($this->checkLatLon($params)!==true){

            return $this->error->_('1001');
        }

        Log::info('ServiceArea::check params:', $params);

        //--快速上單不用檢查開放區域，直接return true
        if($is_skip_check_open_zip===false){
            $res = $this->checkZipIsOpen($params);
            if($res!==true){

                return $res;
            }
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

    private function getZipFromParams(array $params){
        if(!isset($params['zip']) || empty($params['zip'])){
            if(isset($params['city']) && isset($params['district'])) {
                $location = LatLonService::citydistrictFromCityAndDistrict($params['city'], $params['district']);
                try {
                    $zip = $location['zip'];

                    return $zip;
                }catch(\Exception $e){

                }
            }

            try {
                $res = TwddLocation::getDistrict($params['lat'], $params['lon']);
                Log::info('TwddLocation::getDistrict: '.$params['lat'].','.$params['lon'], [$res]);
                if (!is_null($res)) {
                    return substr($res['zip_code'], 0, 3);
                }
            } catch (\Exception $e) {
                Log::error('TwddLocation::getDistrict error', [$e]);
            }

            $lat_lon = $params['lat'].','.$params['lon'];
            $location = Geocoding::reverseGeocode($lat_lon)['data'] ?? [];
            $zip = $location['zip'] ?? 0;
        }else{
            $zip = $params['zip'];
        }

        $zip = substr($zip, 0, 3);

        return $zip;
    }

    private function checkZipIsOpen(array $params){
        $zip = $this->getZipFromParams($params);
        if(strlen($zip)!=3){

            return $this->error->_('1002');
        }
        $districts = $this->repository->allIsopen();
        $district = $districts->where('zip', $zip)->where('isopen', 1);
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
