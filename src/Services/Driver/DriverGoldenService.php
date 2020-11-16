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

    /*
     * 叩取駕駛的金牌
     * $is_minus_by_member_rated 是否因為被用戶評為一星而叩金牌
     * $is_auto_close_driver_gold 是否要關閉駕駛的使用金牌
     */
    public function minusOneByTask(Model $task = null, bool $is_minus_by_member_rated = false, bool $is_auto_close_driver_gold = false) : bool{
        if(is_null($task) || !isset($task->driver_id)) return false;

        $driver = $this->driverRepository->findGoldenById($task->driver_id);
        if($driver->driver_gold_nums==0) {

            return false;
        }

        /*
        $isReduce = $this->driverRepository->reduceGoldenNums($task->driver_id, true);
        //--已經0張的，就不要再叩下去了
        if($isReduce===false){
            return false;
        }
        */

        DB::beginTransaction();
        try{
            $driver->driver_gold_nums = $driver->driver_gold_nums - 1;
            if($is_auto_close_driver_gold === true) {
                $driver->is_used_gold = 0;
            }
            $driver->save();

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