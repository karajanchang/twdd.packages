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
use Illuminate\Support\Facades\Log;
use Twdd\Errors\DriverErrors;
use Twdd\Facades\LatLonService;
use Twdd\Facades\TwddCache;
use Twdd\Jobs\Driver\DriverLocationJob;
use Twdd\Jobs\Driver\MogoDriverLatLonJob;
use Twdd\Repositories\DriverRepository;
use Twdd\Repositories\TaskRepository;
use Twdd\Services\ServiceAbstract;
use Twdd\Traits\ModelToolTrait;

class DriverService extends ServiceAbstract
{
    use ModelToolTrait;

    private $driver = null;
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    public function __construct(DriverErrors $error, TaskRepository $taskRepository)
    {
        $this->error = $error;
        $this->taskRepository = $taskRepository;
    }

    public function driver(Model $driver){
        $this->driver = $driver;

        return $this;
    }

    public function lastTask(array $columns = ['*']){

        return $this->taskRepository->lastTaskByDriverId($this->driver->id, $columns);
    }

    public function intask(){
        if($this->driver->DriverState==2){

            return true;
        }

        //---沒有在進行中的任務，無法變任務中
        $last_task = $this->lastTask(['id', 'TaskState']);
        if(isset($last_task->TaskState) && ($last_task->TaskState==-1 xor $last_task->TaskState==7)){

            return $this->error->_('4003');
        }

        //---更改db DriverState
        return $this->changeDriverState(2);
    }

    public function offline(array $params = []){
        try {
            $res = $this->validateAttributesAndParams($params);
            if ($res !== true) {

                return $res;
            }
        }catch (\Exception $e){
            $this->error->setReplaces('400', ['message' => $e->getMessage()]);

            return $this->error->_('400');
        }

        if($this->driver->DriverState==2){

            return $this->error->_('1012');
        }

        //---有在進行中的任務，無法上下線
        $last_task = $this->lastTask(['id', 'TaskState']);
        if(isset($last_task->TaskState) && $last_task->TaskState>=0 && $last_task->TaskState<7){

            return $this->error->_('4001');
        }

        //---得到city_id district_id  or zip
        if(isset($this->params['lat']) && isset($this->params['lon'])){
            $this->getCitydistrictFromParams();
        }

        //---寫入到mongodb
        dispatch(new MogoDriverLatLonJob($this->driver, $this->params, $this->attrs, 2));

        //---更改db DriverState
        return $this->changeDriverState(0);
    }

    public function online(array $params = []){
        try {
            $res = $this->validateAttributesAndParams($params);
            if ($res !== true) {

                return $res;
            }
        }catch (\Exception $e){
            $this->error->setReplaces('400', ['message' => $e->getMessage()]);

            return $this->error->_('400');
        }

        if($this->driver->is_online!=1){

            return $this->error->_('1005');
        }

        if($this->driver->is_out==1){

            return $this->error->_('1006');
        }

        if((int) $this->driver->DriverCredit < 300){

            return $this->error->_('1007');
        }

        if($this->driver->DriverNew<2){

            return $this->error->_('1008');
        }

        if($this->driver->is_pass_rookie==0 && $this->driver->isARookie()===true){
            Log::info('driver', [$this->driver]);

            return $this->error->_('1009', [
                'start' => env('OLDBIRD_HOUR_START', 1),
                'end' => env('OLDBIRD_HOUR_END', 5),
                'nums' => ($this->driver->pass_rookie_times - $this->driver->DriverServiceTime),
            ]);
        }

        if ($this->driver->isPayForAccidentInsurance($this->driver->id)!==true) {

            return $this->error->_('4004');
        }

        //---有在進行中的任務，無法上線
        $last_task = $this->lastTask(['id', 'TaskState']);
        if(isset($last_task->TaskState) && $last_task->TaskState>=0 && $last_task->TaskState<7){

            return $this->error->_('4002');
        }

        //---得到city_id district_id  or zip
        if(isset($this->params['lat']) && isset($this->params['lon'])){
            $this->getCitydistrictFromParams();
        }

        //---更新資料庫DriverLocation時間或lat lon
        dispatch(new DriverLocationJob($this->driver, $this->params));

        //---寫入到mongodb
        dispatch(new MogoDriverLatLonJob($this->driver, $this->params, $this->attrs, 1));

        //---更改db DriverState
        return $this->changeDriverState(1);
    }



    private function changeDriverState(int $DriverState){
        if(!isset($this->driver->id)){

            return $this->error->_('1001');
        }
        $app = app()->make(\Twdd\Services\Driver\DriverState::class);
        $res = $app->changeDriverState($this->driver->id, $DriverState);
        if($res==1){
            //--寫入Cache
            $this->profile($this->driver->id, ['*'], true);

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

    public function profile(int $id, array $columns = ['*'], $clear_cache = false){
        $repository = app()->make(DriverRepository::class);

        $default_profile = TwddCache::driver($id)->DriverProfile()->key('DriverProfile', $id)->get();
        if(!$default_profile || $clear_cache===true){
            $default_profile = $repository->profile($id);
            TwddCache::driver($id)->DriverProfile()->key('DriverProfile', $id)->put($default_profile);
        }

        $all_columns = ['*'];
        if(count(array_diff($columns, $all_columns))==0){

            return $default_profile;
        }

        if($this->checkColumnsIsExistsInThisModel($columns, $default_profile)===false){

            return $repository->find($id, $columns);
        }

        return $default_profile;
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

