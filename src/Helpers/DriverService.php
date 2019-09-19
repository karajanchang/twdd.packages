<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-05-09
 * Time: 14:23
 */

namespace Twdd\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Twdd\Errors\DriverErrors;
use Twdd\Facades\GoogleMap;
use Twdd\Facades\LatLonService;
use Twdd\Facades\TwddCache;
use Twdd\Jobs\Driver\DriverLocationJob;
use Twdd\Jobs\Driver\MogoDriverLatLonJob;
use Twdd\Repositories\DriverRepository;
use Twdd\Services\ServiceAbstract;

class DriverService extends ServiceAbstract
{
    private $driver = null;

    public function __construct(DriverErrors $error)
    {
        $this->error = $error;
    }

    public function driver(Model $driver){
        $this->driver = $driver;

        return $this;
    }


    public function offline(){
        $res = $this->validateAttributesAndParams();
        if($res!==true){

            return $res;
        }

        if($this->driver->DriverState==2){

            return $this->error_('1012');
        }

        ///---寫入到mongodb
        dispatch(new MogoDriverLatLonJob($this->driver, $this->params, $this->attributes, 2));

        //---更改db DriverState
        return $this->changeDriverState(0);
    }

    public function online(){
        $res = $this->validateAttributesAndParams();
        if($res!==true){

            return $res;
        }

        if($this->driver->is_online!=1){

            return $this->error->_('1005');
        }

        if($this->driver->is_out==1){

            return $this->error->_('1006');
        }

        if((int) $this->driver->DriverCredit < 100){

            return $this->error->_('1007');
        }

        if($this->driver->DriverNew<2){

            return $this->error->_('1008');
        }

        if($this->driver->is_pass_rookie==0 && $this->driver->isARookie===true){

            return $this->error->_('1009', [
                'start' => env('OLDBIRD_HOUR_START', 1),
                'end' => env('OLDBIRD_HOUR_END', 5),
                'nums' => ($this->driver->pass_rookie_times - $this->driver->DriverServiceTime),
            ]);
        }

        //---得到city_id district_id  or zip
        if(isset($this->params['lat']) && isset($this->params['lon'])){
            $this->getCitydistrictFromParams();
        }

        //---更新資料庫DriverLocation時間或lat lon
        dispatch(new DriverLocationJob($this->driver, $this->params));

        //---寫入到mongodb
        dispatch(new MogoDriverLatLonJob($this->driver, $this->params, $this->attributes, 1));

        //---更改db DriverState
        return $this->changeDriverState(1);
    }

    public function onservice(){

        //---寫入到mongodb

        //---更改db DriverState
        return $this->changeDriverState(2);
    }

    private function changeDriverState(int $DriverState){
        if(!isset($this->driver->id)){

            return $this->error->_('1001');
        }
        $app = app()->make(\Twdd\Services\Driver\DriverState::class);
        $res = $app->changeDriverState($this->driver->id, $DriverState);
        if($res==1){
            //--寫入Cache
            TwddCache::driver($this->driver->id)->DriverState($this->driver->id)->put($DriverState);

            return true;
        }

        $lut = [
            0 => '3000',
            1 => '3001',
            2 => '3002',
        ];
        $key = Collection::make($lut)->get($DriverState);

        return $this->error->_($key);
    }

    public function profile(int $id, array $columns = ['*']){
        $repository = app()->make(DriverRepository::class);

        return $repository->find($id, $columns);
    }

    private function getCitydistrictFromParams(){
        $zip = isset($this->params['zip']) ? $this->params['zip'] : null;
        $res = LatLonService::citydistrictFromLatlonOrZip($this->params['lat'], $this->params['lon'], $zip);
        $this->params['city_id'] = $res['city_id'];
        $this->params['city'] = $res['city'];
        $this->params['district_id'] = $res['district_id'];
        $this->params['district'] = $res['district'];
        $this->params['zip'] = $res['zip'];
    }

    protected function rules(){

        return [
            'lat' => 'required',
            'lon' => 'required',
            'zip' => 'nullable',
        ];
    }
}

