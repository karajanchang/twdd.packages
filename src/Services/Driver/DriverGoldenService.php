<?php


namespace Twdd\Services\Driver;


use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twdd\Repositories\DriverGoldAlterRecordRepository;
use Twdd\Repositories\DriverRepository;
use Twdd\Services\ServiceAbstract;

class DriverGoldenService extends ServiceAbstract
{
    /**
     * @var DriverRepository
     */
    private $driverRepository;
    /**
     * @var DriverGoldAlterRecordRepository
     */
    private $driverGoldAlterRecordRepository;

    public function __construct(DriverRepository $driverRepository, DriverGoldAlterRecordRepository $driverGoldAlterRecordRepository)
    {

        $this->driverRepository = $driverRepository;
        $this->driverGoldAlterRecordRepository = $driverGoldAlterRecordRepository;
    }

    public function minusOneByTask(Model $task = null, bool $is_minus_by_member_rated = false) : bool{
        if(is_null($task) || !isset($task->driver_id)) return false;

        DB::beginTransaction();
        try{
            $isReduce = $this->driverRepository->reduceGoldenNums($task->driver_id, true);
            //--已經0張的，就不要再叩下去了
            if($isReduce===false){

                return false;
            }
            $res = $this->driverGoldAlterRecordRepository->insertMinusRecordByTask($task, $is_minus_by_member_rated);
            if($res) {
                Log::info(__CLASS__ . '::' . __METHOD__ . ' 叩除成功: ', [$res]);
            }
            DB::commit();

            return true;
        }catch (\Exception $e){
            DB::rollBack();
            Log::error(__CLASS__.'::'.__METHOD__.' error: '.$e->getMessage(), [$e]);
            Bugsnag::notifyException($e);
        }

        return false;
    }
}