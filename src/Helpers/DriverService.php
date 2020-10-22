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

     /*
     * @params $is_intask_by_others 1被客人在任務中 2被客服在任務中
     */
    public function intask($is_intask_by_others = 0){
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
    /*
     * @params $is_offline_by_others 1被客人下線 2被客服下線 $force_offline 強制下線，不檢查
     *   DriverService::driver()->offline([], 2, true);
     */
    public function offline(array $params = [], int $is_offline_by_others = 0, bool $force_offline = false){
        if($is_offline_by_others==0) {
            try {
                $res = $this->validateAttributesAndParams($params);
                if ($res !== true) {

                    return $res;
                }
            } catch (\Exception $e) {
                $this->error->setReplaces('400', ['message' => $e->getMessage()]);

                return $this->error->_('400');
            }

            //---強制下線時就不用檢查了
            if($force_offline===false) {
                //--若司機DriverState是2，但有進行中的任務才返回1012錯誤
                if ($this->driver->DriverState == 2) {
                    if(app(TaskRepository::class)->checkNotHaveInProcessTaskByDriverId($this->driver->id)===false){

                        return $this->error->_('1012');
                    }
                }

                //---有在進行中的任務，無法上下線
                $last_task = $this->lastTask(['id', 'TaskState']);
                if (isset($last_task->TaskState) && $last_task->TaskState >= 0 && $last_task->TaskState < 7) {

                    return $this->error->_('4001');
                }
            }else{
                Log::info(__CLASS__.'::'.__METHOD__.' 強制下線 $force_offline===true');
            }

            //---得到city_id district_id  or zip
            if (isset($this->params['lat']) && isset($this->params['lon'])) {
                $this->getCitydistrictFromParams();
            }


            //---寫入到mongodb
            dispatch(new MogoDriverLatLonJob($this->driver, $this->params, $this->attrs, 2));
        }else{
            Log::info('司機被下線 is_online_by_others (1客人app 2客服) : ', [$is_offline_by_others]);
        }

        //---更改db DriverState
        return $this->changeDriverState(0);
    }

    /*
     * 快速上單上線
     */
    public function onlineFast(array $params = []){

        return $this->online($params, 0, true);
    }
    /*
     * @param $is_online_by_others = 1 阁用戶上線 2 被客服上線
     */
    public function online(array $params = [], int $is_online_by_others = 0, bool $is_fast_match = false){
        if($is_online_by_others==0) {
            try {
                $res = $this->validateAttributesAndParams($params);
                if ($res !== true) {

                    return $res;
                }
            } catch (\Exception $e) {
                $this->error->setReplaces('400', ['message' => $e->getMessage()]);

                return $this->error->_('400');
            }

            //--停權
            if ($this->driver->is_online != 1) {

                return $this->error->_('1005');
            }

            //--退出
            if ($this->driver->is_out == 1) {

                return $this->error->_('1006');
            }

            //--儲值金不足300元
            if ((int)$this->driver->DriverCredit < 300) {

                return $this->error->_('1007');
            }

            //--身份別不是司機
            if ($this->driver->DriverNew != 2) {

                return $this->error->_('1008');
            }

            //---尚未破冬不能上線，但不包含快速上單
            if ($is_fast_match===false && $this->driver->is_pass_rookie == 0 && $this->driver->isARookie() === true) {
                Log::info('driver', [$this->driver]);

                return $this->error->_('1009', [
                    'start' => env('OLDBIRD_HOUR_START', 1),
                    'end' => env('OLDBIRD_HOUR_END', 5),
                    'nums' => ($this->driver->pass_rookie_times - $this->driver->DriverServiceTime),
                ]);
            }

            //--暫時停權
            if($this->driver->is_tmp_offline===true){

                return $this->error->_('1010');
            }

            if ($this->driver->isPayForAccidentInsurance($this->driver->id) !== true) {

                return $this->error->_('4004');
            }

            //---有在進行中的任務，無法上線
            $last_task = $this->lastTask(['id', 'TaskState']);
            if (isset($last_task->TaskState) && $last_task->TaskState >= 0 && $last_task->TaskState < 7) {

                return $this->error->_('4002');
            }

            //---得到city_id district_id  or zip
            if (isset($this->params['lat']) && isset($this->params['lon'])) {
                $this->getCitydistrictFromParams();
            }

            //---更新資料庫DriverLocation時間或lat lon
            dispatch(new DriverLocationJob($this->driver, $this->params));

            //---寫入到mongodb
            dispatch(new MogoDriverLatLonJob($this->driver, $this->params, $this->attrs, 1));
        }else{
            Log::info('司機被上線 is_online_by_others (1客人app 2客服) : ', [$is_online_by_others]);
        }

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

